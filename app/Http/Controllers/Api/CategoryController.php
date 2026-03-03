<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $query = $request->user()->categories()
            ->when($request->type, fn ($q, $t) => $q->where('type', $t))
            ->orderBy('name');

        return CategoryResource::collection($query->paginate($request->per_page ?? 50))
            ->additional(['success' => true]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'  => 'required|string|max:255',
            'color' => 'nullable|string',
            'icon'  => 'nullable|string',
            'type'  => 'required|in:task,note,finance,goal,bank',
        ]);

        $category = $request->user()->categories()->create($validated);

        return $this->success(new CategoryResource($category), 'Kategori berhasil dibuat', 201);
    }

    public function show(Request $request, Category $category)
    {
        $this->authorizeUser($request, $category);

        return $this->success(new CategoryResource($category));
    }

    public function update(Request $request, Category $category)
    {
        $this->authorizeUser($request, $category);

        $validated = $request->validate([
            'name'  => 'sometimes|string|max:255',
            'color' => 'nullable|string',
            'icon'  => 'nullable|string',
            'type'  => 'sometimes|in:task,note,finance,goal,bank',
        ]);

        $category->update($validated);

        return $this->success(new CategoryResource($category), 'Kategori berhasil diperbarui');
    }

    public function destroy(Request $request, Category $category)
    {
        $this->authorizeUser($request, $category);
        $category->delete();

        return $this->success(null, 'Kategori berhasil dihapus');
    }

    private function authorizeUser(Request $request, Category $category)
    {
        if ($category->user_id !== $request->user()->id) {
            abort(403, 'Unauthorized');
        }
    }
}
