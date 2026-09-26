<?php

namespace Webkul\Admin\Http\Controllers\Insurance;

use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Lead\Models\InsurancePolicy;
use Webkul\Lead\Services\PolicyRetentionService;

class BookOfBusinessController extends Controller
{
    public function __construct(
        protected PolicyRetentionService $retentionService
    ) {}

    /**
     * Display the Book of Business dashboard, retention metrics and policies ledger.
     */
    public function index(Request $request): View|JsonResponse
    {
        // Compute retention KPIs
        $metrics = $this->retentionService->getRetentionMetrics();

        $query = InsurancePolicy::with(['lead.person', 'user', 'person']);

        // Filter: Status
        if ($request->filled('status') && $request->status !== 'all') {
            if ($request->status === 'in_grace') {
                $query->whereIn('status', ['grace_period_1', 'grace_period_2_3']);
            } else {
                $query->where('status', $request->status);
            }
        }

        // Filter: Carrier
        if ($request->filled('carrier') && $request->carrier !== 'all') {
            $query->where('carrier_name', $request->carrier);
        }

        // Filter: Search
        if ($request->filled('search')) {
            $term = $request->search;
            $query->where(function ($q) use ($term) {
                $q->where('policy_number', 'like', "%{$term}%")
                    ->orWhere('plan_name', 'like', "%{$term}%")
                    ->orWhereHas('person', fn ($p) => $p->where('name', 'like', "%{$term}%"))
                    ->orWhereHas('lead.person', fn ($p) => $p->where('name', 'like', "%{$term}%"));
            });
        }

        $policies = $query->latest('id')->paginate(25);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'metrics' => $metrics,
                'policies' => $policies,
            ]);
        }

        return view('admin::insurance.policies.index', [
            'metrics' => $metrics,
            'policies' => $policies,
            'currentStatus' => $request->input('status', 'all'),
            'currentCarrier' => $request->input('carrier', 'all'),
            'search' => $request->input('search', ''),
        ]);
    }

    /**
     * Record a premium payment to restore policy to Active and clear grace period.
     */
    public function recordPayment(Request $request, int $id): JsonResponse
    {
        $policy = InsurancePolicy::findOrFail($id);

        $validated = $request->validate([
            'paid_to_date' => 'required|date',
            'notes' => 'nullable|string|max:500',
        ]);

        $newPaidTo = Carbon::parse($validated['paid_to_date']);

        $policy->update([
            'paid_to_date' => $newPaidTo->toDateString(),
            'status' => 'active',
            'grace_period_days' => 0,
            'grace_period_start_date' => null,
            'notes' => trim(($policy->notes ?: '')."\n[".now()->toDateString().'] Pago verificado hasta '.$newPaidTo->format('d/m/Y').'. Póliza restaurada a Vigente (Al Día).'),
        ]);

        return response()->json([
            'success' => true,
            'message' => "¡Pago registrado para la póliza {$policy->policy_number}! La póliza ha sido restaurada a estado Vigente.",
            'policy' => $policy,
        ]);
    }

    /**
     * Record initial binder payment and effectuate policy coverage.
     */
    public function recordBinderPayment(Request $request, int $id): JsonResponse
    {
        $policy = InsurancePolicy::findOrFail($id);

        $validated = $request->validate([
            'confirmation_number' => 'required|string|max:100',
            'payment_method' => 'required|string|max:50',
            'paid_at' => 'nullable|date',
            'paid_to_date' => 'nullable|date',
            'notes' => 'nullable|string|max:500',
        ]);

        $policy->recordBinderPayment(
            $validated['confirmation_number'],
            $validated['payment_method'],
            $validated['paid_at'] ?? null,
            $validated['paid_to_date'] ?? null,
            auth()->guard('user')->id(),
            $validated['notes'] ?? null
        );

        // Also if policy is linked to lead, record an activity
        if ($policy->lead_id) {
            try {
                $activity = \Webkul\Activity\Models\ActivityProxy::modelClass()::create([
                    'title' => "Primer Pago (Binder) Confirmado - Póliza #{$policy->policy_number}",
                    'type' => 'note',
                    'comment' => "Pago inicial verificado exitosamente con confirmación #{$validated['confirmation_number']} vía {$validated['payment_method']}. Cobertura médica activada y en vigor.",
                    'user_id' => auth()->guard('user')->id() ?: $policy->user_id,
                ]);

                \Illuminate\Support\Facades\DB::table('lead_activities')->insert([
                    'lead_id' => $policy->lead_id,
                    'activity_id' => $activity->id,
                ]);
            } catch (\Throwable $e) {
                // Ignore if activity fails
            }
        }

        return response()->json([
            'success' => true,
            'message' => "¡Pago inicial (Binder) registrado con éxito para la póliza {$policy->policy_number}! La cobertura médica ha sido efectuada y activada.",
            'policy' => $policy->fresh(['coverageHistories']),
        ]);
    }

    /**
     * Get coverage history timeline for a policy.
     */
    public function coverageHistory(int $id): JsonResponse
    {
        $policy = InsurancePolicy::with(['coverageHistories.verifiedBy'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'policy_number' => $policy->policy_number,
            'status' => $policy->status,
            'binder_payment_status' => $policy->binder_payment_status,
            'effectuation_date' => $policy->effectuation_date?->toDateString(),
            'effectuation_source' => $policy->effectuation_source,
            'history' => $policy->coverageHistories,
        ]);
    }

    /**
     * Get preformatted WhatsApp payment reminder for client.
     */
    public function getWhatsAppReminder(int $id): JsonResponse
    {
        $policy = InsurancePolicy::with(['person', 'lead.person'])->findOrFail($id);

        $person = $policy->person ?: $policy->lead?->person;
        $phone = '';

        if ($person && ! empty($person->contact_numbers)) {
            $raw = $person->contact_numbers[0]['value'] ?? '';
            $digits = preg_replace('/\D/', '', $raw);
            $phone = strlen($digits) === 10 ? '1'.$digits : $digits;
        }

        $message = $policy->whatsapp_payment_reminder;
        $url = 'https://api.whatsapp.com/send?text='.urlencode($message);

        if ($phone) {
            $url = "https://api.whatsapp.com/send?phone={$phone}&text=".urlencode($message);
        }

        return response()->json([
            'success' => true,
            'phone' => $phone,
            'message' => $message,
            'whatsapp_url' => $url,
        ]);
    }

    /**
     * Mark policy as renewed for the upcoming plan year.
     */
    public function renew(Request $request, int $id): JsonResponse
    {
        $policy = InsurancePolicy::findOrFail($id);

        $nextYear = Carbon::parse($policy->renewal_date ?: now())->addYear();

        $policy->update([
            'status' => 'renewed',
            'renewal_date' => $nextYear->toDateString(),
            'notes' => trim(($policy->notes ?: '')."\n[".now()->toDateString().'] Póliza renovada para el año '.$nextYear->year.'.'),
        ]);

        return response()->json([
            'success' => true,
            'message' => "Póliza {$policy->policy_number} renovada con éxito.",
            'policy' => $policy,
        ]);
    }

    /**
     * Trigger manual grace period evaluation scan.
     */
    public function scanGracePeriods(): JsonResponse
    {
        $stats = $this->retentionService->evaluateGracePeriods();

        return response()->json([
            'success' => true,
            'message' => "Escaneo de cartera completado: {$stats['total_evaluated']} pólizas analizadas.",
            'stats' => $stats,
        ]);
    }
}
