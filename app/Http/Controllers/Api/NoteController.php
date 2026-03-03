<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Http\Resources\NoteResource;
use App\Models\Note;
use Illuminate\Http\Request;

class NoteController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $query = $request->user()->notes()
            ->with('category')
            ->when($request->category_id, fn ($q, $c) => $q->where('category_id', $c))
            ->when($request->search, fn ($q, $s) => $q->where('title', 'like', "%{$s}%"))
            ->when($request->boolean('is_pinned'), fn ($q) => $q->where('is_pinned', true))
            ->when($request->boolean('is_favorite'), fn ($q) => $q->where('is_favorite', true))
            ->orderByDesc('is_pinned')
            ->latest();

        return NoteResource::collection($query->paginate($request->per_page ?? 15))
            ->additional(['success' => true]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'       => 'required|string|max:255',
            'content'     => 'nullable|string',
            'category_id' => 'nullable|exists:categories,id',
            'tags'        => 'nullable|array',
            'is_pinned'   => 'nullable|boolean',
            'is_favorite' => 'nullable|boolean',
            'color'       => 'nullable|string',
        ]);

        $note = $request->user()->notes()->create($validated);

        return $this->success(new NoteResource($note->load('category')), 'Catatan berhasil dibuat', 201);
    }

    public function show(Request $request, Note $note)
    {
        $this->authorizeUser($request, $note);

        return $this->success(new NoteResource($note->load('category')));
    }

    public function update(Request $request, Note $note)
    {
        $this->authorizeUser($request, $note);

        $validated = $request->validate([
            'title'       => 'sometimes|string|max:255',
            'content'     => 'nullable|string',
            'category_id' => 'nullable|exists:categories,id',
            'tags'        => 'nullable|array',
            'is_pinned'   => 'nullable|boolean',
            'is_favorite' => 'nullable|boolean',
            'color'       => 'nullable|string',
        ]);

        $note->update($validated);

        return $this->success(new NoteResource($note->load('category')), 'Catatan berhasil diperbarui');
    }

    public function destroy(Request $request, Note $note)
    {
        $this->authorizeUser($request, $note);
        $note->delete();

        return $this->success(null, 'Catatan berhasil dihapus');
    }

    private function authorizeUser(Request $request, Note $note)
    {
        if ($note->user_id !== $request->user()->id) {
            abort(403, 'Unauthorized');
        }
    }
}
