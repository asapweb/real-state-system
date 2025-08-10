<?php

namespace App\Http\Controllers;

use App\Http\Resources\AttachmentResource;
use App\Models\ContractExpense;
use App\Models\Attachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class ContractExpenseAttachmentController extends Controller
{
    public function index(ContractExpense $contractExpense)
    {
        return AttachmentResource::collection(
            $contractExpense->attachments()->latest()->get()
        );
    }

    public function store(Request $request, ContractExpense $contractExpense)
    {
        $validated = $request->validate([
            'file' => 'required|file|max:5120|mimes:pdf,jpg,jpeg,png',
        ]);

        $path = $validated['file']->store('attachments/contract_expenses', 'public');

        $attachment = $contractExpense->attachments()->create([
            'filename' => $validated['file']->getClientOriginalName(),
            'path' => $path,
            'mime_type' => $validated['file']->getMimeType(),
            'uploaded_by' => $request->user()->id,
        ]);

        return new AttachmentResource($attachment);
    }


    /**
     * Descargar un adjunto específico.
     */
    public function download(ContractExpense $contractExpense, Attachment $attachment)
    {
        // Verificar que el adjunto pertenece al gasto
        if ($attachment->attachable_id !== $contractExpense->id || $attachment->attachable_type !== ContractExpense::class) {
            return response()->json(['message' => 'El adjunto no pertenece a este gasto.'], 403);
        }

        return Storage::disk('public')->download($attachment->path, $attachment->filename);
    }

    /**
     * Eliminar un adjunto.
     */
    public function destroy(ContractExpense $contractExpense, Attachment $attachment)
    {
        // Verificar que el adjunto pertenece al gasto
        if ($attachment->attachable_id !== $contractExpense->id || $attachment->attachable_type !== ContractExpense::class) {
            return response()->json(['message' => 'El adjunto no pertenece a este gasto.'], 403);
        }

        // Borrar archivo físico y registro
        Storage::disk('public')->delete($attachment->path);
        $attachment->delete();

        return response()->json(['message' => 'Adjunto eliminado correctamente.']);
    }
}
