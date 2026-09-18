<?php

namespace Webkul\Admin\Http\Controllers\Quote;

use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Quote\Models\HealthPlanProposal;
use Webkul\Quote\Models\Quote;

class ProposalPortalController extends Controller
{
    /**
     * Display public side-by-side health plan comparison to the beneficiary.
     */
    public function show(string $token): View
    {
        $proposal = HealthPlanProposal::with(['lead.person', 'user'])->where('token', $token)->firstOrFail();

        // Mark as viewed if first time
        if ($proposal->status === 'sent') {
            $proposal->update([
                'status' => 'viewed',
                'viewed_at' => Carbon::now(),
                'client_ip' => request()->ip(),
            ]);
        }

        $quotes = $proposal->getQuotes();
        $lead = $proposal->lead;
        $agent = $proposal->user ?: $lead?->user;

        return view('admin::quotes.portal.proposal', [
            'proposal' => $proposal,
            'quotes' => $quotes,
            'lead' => $lead,
            'agent' => $agent,
        ]);
    }

    /**
     * Beneficiary selects their chosen plan directly from their phone/computer.
     */
    public function selectPlan(Request $request, string $token): JsonResponse
    {
        $proposal = HealthPlanProposal::where('token', $token)->firstOrFail();

        $validated = $request->validate([
            'quote_id' => 'required|integer',
            'notes' => 'nullable|string|max:1000',
        ]);

        $quote = Quote::where('id', $validated['quote_id'])
            ->whereIn('id', $proposal->quote_ids)
            ->firstOrFail();

        $proposal->update([
            'selected_quote_id' => $quote->id,
            'status' => 'accepted',
            'accepted_at' => Carbon::now(),
            'client_notes' => $validated['notes'] ?? null,
            'client_ip' => request()->ip(),
        ]);

        $quote->update([
            'quote_status' => 'accepted',
        ]);

        return response()->json([
            'success' => true,
            'message' => '¡Excelente! Ha seleccionado el plan '.$quote->plan_name.'. Su agente ha sido notificado.',
            'redirect_url' => route('proposal.portal.thankyou', $token),
        ]);
    }

    /**
     * Thank you confirmation screen.
     */
    public function thankYou(string $token): View
    {
        $proposal = HealthPlanProposal::with(['lead.person', 'user', 'selectedQuote'])->where('token', $token)->firstOrFail();

        return view('admin::quotes.portal.thankyou', [
            'proposal' => $proposal,
            'selectedQuote' => $proposal->selectedQuote,
            'lead' => $proposal->lead,
            'agent' => $proposal->user ?: $proposal->lead?->user,
        ]);
    }
}
