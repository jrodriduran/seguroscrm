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

        if ($request->ajax()) {
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
