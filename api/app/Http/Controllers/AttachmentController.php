<?php

namespace App\Http\Controllers;

use App\Models\Attachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use App\Http\Resources\AttachmentResource;

class AttachmentController extends Controller
{
    public function index(Request $request, $type, $id)
    {
        // Parámetros de paginación y ordenamiento
        $perPage = $request->input('per_page', 10);
        $sortBy = $request->input('sort_by', 'id');
        $sortDirection = strtolower($request->input('sort_direction', 'asc')) === 'desc' ? 'desc' : 'asc';

        // Validación básica de campos ordenables
        $allowedSorts = ['name', 'mime_type', 'size', 'created_at'];
        if (!in_array($sortBy, $allowedSorts)) {
            $sortBy = 'id';
        }

        $model = $this->resolveModel($type)::findOrFail($id);

        $query = $model->attachments()->with(['category', 'uploadedBy']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%$search%")
                ->orWhere('mime_type', 'like', "%$search%");
            });
        }

        if ($request->filled('category_id')) {
            $query->where('attachment_category_id', $request->category_id);
        }

        // Orden y paginación
        $query->orderBy($sortBy, $sortDirection);
        return AttachmentResource::collection($query->paginate($perPage));
    }

    protected function resolveModel($type)
    {
        return match ($type) {
            'client' => \App\Models\Client::class,
            'property' => \App\Models\Property::class,
            'contract' => \App\Models\Contract::class,
            'contract-expense' => \App\Models\ContractExpense::class,
            'rental_offer' => \App\Models\RentalOffer::class,
            'rental-application' => \App\Models\RentalApplication::class,
            default => abort(404, 'Entidad no válida'),
        };
    }

    public function store(Request $request, $type, $id)
{
    $model = $this->resolveModel($type)::findOrFail($id);

    $request->validate([
        'file' => 'required|file|max:10240', // 10 MB
        'attachment_category_id' => 'required|exists:attachment_categories,id',
        'name' => 'nullable|string|max:255',
    ]);

    if (!$request->hasFile('file')) {
        throw new \Exception('No se recibió archivo');
    }

    $path = $request->file('file')->store('attachments', 's3');

    if (!$path || trim($path) === '') {
        throw new \Exception('Upload failed: empty path returned from store().');
    }

    $attachment = Attachment::create([
        'uploaded_by' => auth()->id(),
        'attachment_category_id' => $request->attachment_category_id,
        'attachable_type' => $model::class,
        'attachable_id' => $model->id,
        'name' => $request->input('name') ?: $request->file('file')->getClientOriginalName(),
        'mime_type' => $request->file('file')->getMimeType(),
        'size' => $request->file('file')->getSize(),
        'file_path' => $path,
        'url' => Storage::disk('s3')->url($path),
    ]);

    return response()->json($attachment, 201);
}


    public function destroy(Attachment $attachment)
    {
        if ($attachment->file_path) {
            Storage::disk('s3')->delete($attachment->file_path);
        }

        $attachment->delete();

        return response()->json(['message' => 'Attachment deleted successfully.']);
    }


    public function show(Attachment $attachment)
    {
        $attachment->url = URL::to('/storage/' . ltrim($attachment->file_path, '/'));
        return new AttachmentResource($attachment->load('category'));
    }
}
