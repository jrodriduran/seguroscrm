<?php

namespace Webkul\Admin\Http\Controllers\Quote;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Core\Traits\PDFHandler;
use Webkul\Lead\Models\Lead;
use Webkul\Quote\Models\HealthPlanProposal;
use Webkul\Quote\Models\Quote;

class HealthPlanProposalController extends Controller
{
    use PDFHandler;

    /**
     * Create or retrieve an active Side-by-Side health plan comparison proposal.
     */
    public function generateOrGet(Request $request, int $leadId): JsonResponse
    {
        $lead = Lead::with(['person', 'quotes'])->findOrFail($leadId);

        $quoteIds = $request->input('quote_ids');
        if (! is_array($quoteIds) || empty($quoteIds)) {
            $quoteIds = $lead->quotes->pluck('id')->take(4)->toArray();
        }

        if (empty($quoteIds)) {
            return response()->json([
                'success' => false,
                'message' => 'No hay cotizaciones registradas para este lead para realizar la comparativa.',
            ], 422);
        }

        // Check if there is an existing pending/sent proposal for these exact quotes
        $proposal = HealthPlanProposal::where('lead_id', $leadId)
            ->where('status', '!=', 'accepted')
            ->latest()
            ->first();

        if (! $proposal) {
            $user = auth()->guard('user')->user() ?: $lead->user;
            $proposal = HealthPlanProposal::create([
                'lead_id' => $leadId,
                'user_id' => $user?->id,
                'token' => Str::random(40),
                'title' => 'Comparativa de Planes de Salud ACA - '.($lead->person?->name ?: $lead->title),
                'quote_ids' => $quoteIds,
                'status' => 'sent',
            ]);
        } else {
            // Update quotes if explicitly provided
            if ($request->has('quote_ids')) {
                $proposal->update(['quote_ids' => $quoteIds]);
            }
        }

        $quotes = Quote::whereIn('id', $proposal->quote_ids)->get();

        $phone = '';
        if ($lead->person && ! empty($lead->person->contact_numbers)) {
            $rawPhone = $lead->person->contact_numbers[0]['value'] ?? '';
            $digits = preg_replace('/\D/', '', $rawPhone);
            $phone = strlen($digits) === 10 ? '1'.$digits : $digits;
        }

        $whatsappUrl = 'https://api.whatsapp.com/send?text='.urlencode($proposal->whatsapp_message);
        if ($phone) {
            $whatsappUrl = "https://api.whatsapp.com/send?phone={$phone}&text=".urlencode($proposal->whatsapp_message);
        }

        return response()->json([
            'success' => true,
            'proposal' => $proposal,
            'quotes' => $quotes,
            'public_url' => $proposal->public_url,
            'pdf_url' => route('admin.quotes.proposals.download_pdf', $proposal->id),
            'print_url' => route('admin.quotes.proposals.print', $proposal->id),
            'whatsapp_url' => $whatsappUrl,
            'whatsapp_message' => $proposal->whatsapp_message,
        ]);
    }

    /**
     * Download side-by-side comparison proposal as PDF.
     */
    public function downloadPdf(int $id)
    {
        $proposal = HealthPlanProposal::with(['lead.person', 'user'])->findOrFail($id);
        $lead = $proposal->lead;
        $quotes = $proposal->getQuotes();

        $html = view('admin::quotes.proposal_pdf', [
            'proposal' => $proposal,
            'lead' => $lead,
            'quotes' => $quotes,
            'agent' => $proposal->user ?: (auth()->guard('user')->user() ?: $lead->user),
            'isPdf' => true,
        ])->render();

        $clientSlug = Str::slug($lead->person?->name ?: $lead->title ?: 'cliente');
        $fileName = 'Propuesta_Planes_Salud_'.$lead->id.'_'.$clientSlug;

        return $this->downloadPDF($html, $fileName);
    }

    /**
     * Preview comparison proposal in browser before printing/sending.
     */
    public function print(int $id)
    {
        $proposal = HealthPlanProposal::with(['lead.person', 'user'])->findOrFail($id);
        $lead = $proposal->lead;
        $quotes = $proposal->getQuotes();

        return view('admin::quotes.proposal_pdf', [
            'proposal' => $proposal,
            'lead' => $lead,
            'quotes' => $quotes,
            'agent' => $proposal->user ?: (auth()->guard('user')->user() ?: $lead->user),
            'isPdf' => false,
        ]);
    }
}
