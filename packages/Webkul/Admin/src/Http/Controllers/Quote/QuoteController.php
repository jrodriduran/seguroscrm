<?php

namespace Webkul\Admin\Http\Controllers\Quote;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Event;
use Illuminate\View\View;
use Prettus\Repository\Criteria\RequestCriteria;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Webkul\Admin\DataGrids\Quote\QuoteDataGrid;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Admin\Http\Requests\AttributeForm;
use Webkul\Admin\Http\Requests\MassDestroyRequest;
use Webkul\Admin\Http\Resources\QuoteResource;
use Webkul\Attribute\Repositories\AttributeRepository;
use Webkul\Core\Traits\PDFHandler;
use Webkul\Lead\Repositories\LeadRepository;
use Webkul\Quote\Repositories\QuoteRepository;

class QuoteController extends Controller
{
    use PDFHandler;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(
        protected QuoteRepository $quoteRepository,
        protected LeadRepository $leadRepository,
        protected AttributeRepository $attributeRepository
    ) {
        request()->request->add(['entity_type' => 'quotes']);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): View|JsonResponse
    {
        if (request()->ajax()) {
            return datagrid(QuoteDataGrid::class)->process();
        }

        return view('admin::quotes.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $leadId = request('lead_id');

        $lead = $leadId ? $this->leadRepository->find($leadId) : null;

        $quote = $this->quoteRepository->getModel();

        if ($lead) {
            $quote->fill([
                'person_id' => $lead->person_id,
                'user_id' => $lead->user_id,
                'billing_address' => $lead->person->organization?->address,
                'expired_at' => $lead->expected_close_date ?? now()->toDateString(),
            ]);
        }

        $leadProducts = $this->getLeadProductsForQuote($lead);

        $lookUpEntityData = $this->attributeRepository->getLookUpEntity('leads', $leadId);

        return view('admin::quotes.create', compact('lead', 'quote', 'leadProducts', 'lookUpEntityData'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(AttributeForm $request): RedirectResponse|JsonResponse
    {
        $this->ensureHealthQuoteData($request);

        if (! request()->has('quick_add')) {
            $this->additionalValidation();
        }

        $this->syncShippingAddressWithBilling($request);

        Event::dispatch('quote.create.before');

        $quote = $this->quoteRepository->create($request->all());

        $leadId = request('lead_id');

        if ($leadId) {
            $lead = $this->leadRepository->find($leadId);

            if ($lead) {
                $lead->quotes()->attach($quote->id);

                if ($quote->net_premium > 0 || $quote->grand_total > 0) {
                    $lead->lead_value = $quote->net_premium ?: $quote->grand_total;
                    if (in_array($lead->lead_pipeline_stage_id, [1, 2])) {
                        $lead->lead_pipeline_stage_id = 3;
                    }
                    $lead->save();
                }
            }
        }

        Event::dispatch('quote.create.after', $quote);

        if (request()->ajax()) {
            return response()->json([
                'data' => $quote,
                'message' => trans('admin::app.quotes.index.create-success'),
            ]);
        }

        session()->flash('success', trans('admin::app.quotes.index.create-success'));

        return request()->query('from') === 'lead' && $leadId
            ? redirect()->route('admin.leads.view', ['id' => $leadId, 'from' => 'quotes'])
            : redirect()->route('admin.quotes.index');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(int $id): View
    {
        $quote = $this->quoteRepository->findOrFail($id);

        $this->preventUnauthorizedAccess($quote->user_id);

        $leadId = old('lead_id') ?? optional($quote->leads->first())->id;

        $linkedLead = $leadId ? $this->leadRepository->find($leadId) : null;

        $initialQuoteItems = $quote->items;

        if ($initialQuoteItems->isEmpty() && $linkedLead?->products?->isNotEmpty()) {
            $initialQuoteItems = collect($this->getLeadProductsForQuote($linkedLead));
        }

        $lookUpEntityData = $this->attributeRepository->getLookUpEntity('leads', $leadId);

        return view('admin::quotes.edit', compact('quote', 'linkedLead', 'initialQuoteItems', 'lookUpEntityData'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(AttributeForm $request, int $id): RedirectResponse
    {
        $this->preventUnauthorizedAccess($this->quoteRepository->findOrFail($id)->user_id);

        $this->ensureHealthQuoteData($request);

        $this->additionalValidation();

        $this->syncShippingAddressWithBilling($request);

        Event::dispatch('quote.update.before', $id);

        $quote = $this->quoteRepository->update($request->all(), $id);

        $quote->leads()->detach();

        $leadId = request('lead_id');

        if ($leadId) {
            $lead = $this->leadRepository->find($leadId);

            if ($lead) {
                $lead->quotes()->attach($quote->id);

                if ($quote->net_premium > 0 || $quote->grand_total > 0) {
                    $lead->lead_value = $quote->net_premium ?: $quote->grand_total;
                    $lead->save();
                }
            }
        }

        Event::dispatch('quote.update.after', $quote);

        session()->flash('success', trans('admin::app.quotes.index.update-success'));

        return request()->query('from') === 'lead' && $leadId
            ? redirect()->route('admin.leads.view', ['id' => $leadId, 'from' => 'quotes'])
            : redirect()->route('admin.quotes.index');
    }

    /**
     * Search the quotes.
     */
    public function search(): AnonymousResourceCollection
    {
        $quotes = $this->quoteRepository
            ->pushCriteria(app(RequestCriteria::class))
            ->all();

        return QuoteResource::collection($quotes);
    }

    /**
     * Return products for the selected lead in quote payload format.
     */
    public function leadProducts(int $leadId): JsonResponse
    {
        $lead = $this->leadRepository->findOrFail($leadId);

        return response()->json([
            'data' => $this->getLeadProductsForQuote($lead),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id): JsonResponse
    {
        $this->preventUnauthorizedAccess($this->quoteRepository->findOrFail($id)->user_id);

        try {
            Event::dispatch('quote.delete.before', $id);

            $this->quoteRepository->delete($id);

            Event::dispatch('quote.delete.after', $id);

            return response()->json([
                'message' => trans('admin::app.quotes.index.delete-success'),
            ], 200);
        } catch (\Exception $exception) {
            return response()->json([
                'message' => trans('admin::app.quotes.index.delete-failed'),
            ], 400);
        }
    }

    /**
     * Mass Delete the specified resources.
     */
    public function massDestroy(MassDestroyRequest $massDestroyRequest): JsonResponse
    {
        $quotes = $this->filterAuthorizedRecords(
            $this->quoteRepository->findWhereIn('id', $massDestroyRequest->input('indices'))
        );

        try {
            foreach ($quotes as $quotes) {
                Event::dispatch('quote.delete.before', $quotes->id);

                $this->quoteRepository->delete($quotes->id);

                Event::dispatch('quote.delete.after', $quotes->id);
            }

            return response()->json([
                'message' => trans('admin::app.quotes.index.delete-success'),
            ]);
        } catch (\Exception $exception) {
            return response()->json([
                'message' => trans('admin::app.quotes.index.delete-failed'),
            ], 400);
        }
    }

    /**
     * Print and download the for the specified resource.
     */
    public function print($id): Response|StreamedResponse
    {
        $quote = $this->quoteRepository->findOrFail($id);

        $this->preventUnauthorizedAccess($quote->user_id);

        return $this->downloadPDF(
            view('admin::quotes.pdf', compact('quote'))->render(),
            'Quote_'.$quote->subject.'_'.$quote->created_at->format('d-m-Y')
        );
    }

    /**
     * Mirror the billing address into the shipping address when "same as billing" is enabled.
     */
    private function syncShippingAddressWithBilling(AttributeForm $request): void
    {
        if ($request->boolean('shipping_address_same_as_billing')) {
            $request->merge([
                'shipping_address' => $request->input('billing_address'),
            ]);
        }
    }

    /**
     * Get preformatted WhatsApp proposal text and click-to-chat URL.
     */
    public function getWhatsAppMessage(int $id): JsonResponse
    {
        $quote = $this->quoteRepository->findOrFail($id);
        $message = $quote->getWhatsAppSummary();

        $phone = null;
        if (! empty($quote->person?->contact_numbers)) {
            $rawPhone = $quote->person->contact_numbers[0]['value'] ?? null;
            $digits = preg_replace('/\D+/', '', (string) $rawPhone);
            if ($digits) {
                if (strlen($digits) === 10) {
                    $digits = '1'.$digits;
                }
                $phone = $digits;
            }
        }

        $whatsappUrl = $phone
            ? 'https://api.whatsapp.com/send?phone='.$phone.'&text='.urlencode($message)
            : 'https://api.whatsapp.com/send?text='.urlencode($message);

        return response()->json([
            'status'       => true,
            'client_name'  => $quote->person?->name ?? 'Cliente',
            'client_phone' => $phone,
            'message'      => $message,
            'whatsapp_url' => $whatsappUrl,
        ]);
    }

    /**
     * Convert an accepted quote into an active/bound policy and advance lead to won.
     */
    public function convertToPolicy(int $id): JsonResponse|RedirectResponse
    {
        $quote = $this->quoteRepository->findOrFail($id);
        $this->preventUnauthorizedAccess($quote->user_id);

        $quote->quote_status = 'bound';
        $quote->save();

        $lead = $quote->leads->first();
        if ($lead) {
            $lead->status = 1;
            $lead->lead_pipeline_stage_id = 5;
            $lead->lead_value = $quote->net_premium ?: $quote->grand_total;
            $lead->save();

            Event::dispatch('lead.update.after', $lead);
        }

        $msg = "¡Cotización #{$quote->id} convertida con éxito en Póliza Emitida! El caso de salud ha sido cerrado y ganado.";

        if (request()->ajax()) {
            return response()->json([
                'status'  => true,
                'message' => $msg,
                'quote'   => $quote,
            ]);
        }

        session()->flash('success', $msg);

        return redirect()->route('admin.quotes.index');
    }

    /**
     * Ensure health quote fields, synthetic items, totals, and subject are synchronized.
     */
    private function ensureHealthQuoteData(AttributeForm $request): void
    {
        $carrier = $request->input('carrier_name');
        $plan = $request->input('plan_name');
        $metalTier = $request->input('metal_tier');

        $isHealthQuote = ! empty($carrier) || ! empty($plan) || $request->filled('gross_premium') || $request->filled('aptc_subsidy');

        if (! $isHealthQuote) {
            return;
        }

        $gross = (float) ($request->input('gross_premium') ?: $request->input('net_premium') ?: 0);
        $subsidy = (float) ($request->input('aptc_subsidy') ?: 0);
        $net = (float) ($request->input('net_premium') !== null && $request->input('net_premium') !== '' 
            ? $request->input('net_premium') 
            : max(0, $gross - $subsidy));

        if (! $request->filled('subject')) {
            $subjectParts = array_filter([$carrier, $plan, $metalTier ? ucfirst($metalTier) : null]);
            $request->merge([
                'subject' => ! empty($subjectParts) ? implode(' - ', $subjectParts) : 'Cotización Plan de Salud ACA',
            ]);
        }

        $request->merge([
            'gross_premium'   => $gross,
            'aptc_subsidy'    => $subsidy,
            'net_premium'     => $net,
            'sub_total'       => $gross,
            'discount_amount' => $subsidy,
            'grand_total'     => $net,
            'tax_amount'      => 0,
            'adjustment_amount' => 0,
        ]);

        $items = $request->input('items', []);
        $hasProduct = false;
        if (is_array($items)) {
            foreach ($items as $it) {
                if (! empty($it['product_id'])) {
                    $hasProduct = true;
                    break;
                }
            }
        }

        if (! $hasProduct) {
            $product = null;
            if ($carrier) {
                $product = \Webkul\Product\Models\Product::where('name', 'LIKE', "%{$carrier}%")->first();
            }
            if (! $product) {
                $product = \Webkul\Product\Models\Product::first();
            }

            $request->merge([
                'items' => [
                    'item_0' => [
                        'product_id'      => $product?->id ?? 1,
                        'name'            => trim(($carrier ?: 'Salud') . ' ' . ($plan ?: '')),
                        'quantity'        => 1,
                        'price'           => $gross,
                        'discount_amount' => $subsidy,
                        'tax_amount'      => 0,
                        'total'           => $gross,
                        'final_total'     => $net,
                    ],
                ],
            ]);
        }
    }

    /**
     * Additional validation for quote product items.
     */
    private function additionalValidation(): void
    {
        $this->validate(request(), [
            'items' => 'required|array',
            'items.*.product_id' => 'required',
            'items.*.quantity' => 'required|numeric|min:0',
            'items.*.price' => 'required|numeric|min:0',
            'items.*.total' => 'required|numeric|min:0',
            'items.*.discount_amount' => 'required|numeric|min:0',
            'items.*.tax_amount' => 'required|numeric|min:0',
            'items.*.final_total' => 'required|numeric|min:0',
        ]);
    }

    /**
     * Map linked lead products to quote item payload format.
     */
    private function getLeadProductsForQuote($lead): array
    {
        if (! $lead?->products?->isNotEmpty()) {
            return [];
        }

        return $lead->products
            ->map(function ($product) {
                $quantity = (float) ($product->quantity ?: 1);
                $price = (float) ($product->price ?: 0);

                return [
                    'id' => null,
                    'product_id' => $product->product_id,
                    'name' => $product->name,
                    'quantity' => $quantity,
                    'total' => $price * $quantity,
                    'price' => $price,
                    'discount_amount' => 0,
                    'tax_amount' => 0,
                ];
            })
            ->values()
            ->toArray();
    }
}
