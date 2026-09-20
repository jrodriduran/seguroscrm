<?php

namespace Webkul\Admin\Http\Controllers\Lead;

use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use Webkul\Lead\Models\Lead;
use Webkul\Lead\Models\LeadDmiDocument;

class DmiDocumentController extends Controller
{
    /**
     * List all DMI documents for a lead.
     */
    public function index(int $leadId): JsonResponse
    {
        $lead = Lead::findOrFail($leadId);

        $documents = LeadDmiDocument::where('lead_id', $lead->id)
            ->orderBy('deadline_date', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'documents' => $documents,
            'summary' => [
                'total' => $documents->count(),
                'critical' => $documents->where('urgency_level', 'critical')->count(),
                'warning' => $documents->where('urgency_level', 'warning')->count(),
                'verified' => $documents->where('urgency_level', 'verified')->count(),
                'expired' => $documents->where('urgency_level', 'expired')->count(),
            ],
        ]);
    }

    /**
     * Create a new DMI document requirement.
     */
    public function store(Request $request, int $leadId): JsonResponse
    {
        $lead = Lead::findOrFail($leadId);

        $request->validate([
            'doc_type' => 'required|string|max:50',
            'title' => 'required|string|max:150',
            'notice_date' => 'nullable|date',
            'deadline_date' => 'nullable|date',
            'status' => 'nullable|string|max:40',
            'notes' => 'nullable|string',
            'file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);

        $noticeDate = $request->input('notice_date') ? Carbon::parse($request->input('notice_date')) : Carbon::today();
        $deadlineDate = $request->input('deadline_date')
            ? Carbon::parse($request->input('deadline_date'))
            : $noticeDate->copy()->addDays(90);

        $filePath = null;
        $fileName = null;

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $fileName = $file->getClientOriginalName();
            $filePath = $file->store('dmi_documents/'.$lead->id, 'public');
        }

        $document = LeadDmiDocument::create([
            'lead_id' => $lead->id,
            'person_id' => $lead->person_id,
            'doc_type' => $request->input('doc_type'),
            'title' => $request->input('title'),
            'notice_date' => $noticeDate,
            'deadline_date' => $deadlineDate,
            'status' => $request->input('status', 'pending_upload'),
            'file_path' => $filePath,
            'file_name' => $fileName,
            'notes' => $request->input('notes'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Documento DMI registrado exitosamente.',
            'document' => $document,
        ]);
    }

    /**
     * Update an existing DMI document status or details.
     */
    public function update(Request $request, int $leadId, int $id): JsonResponse
    {
        $lead = Lead::findOrFail($leadId);
        $document = LeadDmiDocument::where('lead_id', $lead->id)->findOrFail($id);

        $request->validate([
            'doc_type' => 'nullable|string|max:50',
            'title' => 'nullable|string|max:150',
            'notice_date' => 'nullable|date',
            'deadline_date' => 'nullable|date',
            'status' => 'nullable|string|max:40',
            'notes' => 'nullable|string',
            'file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);

        $data = $request->only(['doc_type', 'title', 'status', 'notes']);

        if ($request->has('notice_date')) {
            $data['notice_date'] = Carbon::parse($request->input('notice_date'));
        }

        if ($request->has('deadline_date')) {
            $data['deadline_date'] = Carbon::parse($request->input('deadline_date'));
        }

        if ($request->hasFile('file')) {
            if ($document->file_path && Storage::disk('public')->exists($document->file_path)) {
                Storage::disk('public')->delete($document->file_path);
            }

            $file = $request->file('file');
            $data['file_name'] = $file->getClientOriginalName();
            $data['file_path'] = $file->store('dmi_documents/'.$lead->id, 'public');
        }

        $document->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Documento DMI actualizado con éxito.',
            'document' => $document->fresh(),
        ]);
    }

    /**
     * Delete a DMI document.
     */
    public function destroy(int $leadId, int $id): JsonResponse
    {
        $lead = Lead::findOrFail($leadId);
        $document = LeadDmiDocument::where('lead_id', $lead->id)->findOrFail($id);

        if ($document->file_path && Storage::disk('public')->exists($document->file_path)) {
            Storage::disk('public')->delete($document->file_path);
        }

        $document->delete();

        return response()->json([
            'success' => true,
            'message' => 'Documento DMI eliminado correctamente.',
        ]);
    }

    /**
     * Generate WhatsApp reminder message and link for consumer.
     */
    public function getWhatsAppReminder(int $leadId, int $id): JsonResponse
    {
        $lead = Lead::with('person')->findOrFail($leadId);
        $document = LeadDmiDocument::where('lead_id', $lead->id)->findOrFail($id);

        $phone = collect($lead->person?->contact_numbers ?? [])->first()['value'] ?? '';
        $cleanPhone = preg_replace('/[^0-9]/', '', (string) $phone);

        $message = $document->whatsapp_reminder_message;
        $whatsappUrl = 'https://api.whatsapp.com/send?phone='.$cleanPhone.'&text='.urlencode($message);

        return response()->json([
            'success' => true,
            'phone' => $phone,
            'clean_phone' => $cleanPhone,
            'message' => $message,
            'whatsapp_url' => $whatsappUrl,
        ]);
    }
}
