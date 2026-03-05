    <?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PortfolioProjectResource;
use App\Http\Traits\ApiResponse;
use App\Models\PortfolioProject;
use Illuminate\Http\Request;

class PortfolioProjectController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $projects = $request->user()->portfolioProjects()
            ->when($request->category, fn ($q, $c) => $q->where('category', $c))
            ->when($request->boolean('featured'), fn ($q) => $q->where('is_featured', true))
            ->orderBy('sort_order')
            ->orderByDesc('created_at')
            ->get();

        return PortfolioProjectResource::collection($projects)
            ->additional(['success' => true]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'             => 'required|string|max:255',
            'description'       => 'nullable|string',
            'short_description' => 'nullable|string|max:255',
            'image'             => 'nullable|image|max:5120',
            'category'          => 'nullable|string|max:50',
            'demo_url'          => 'nullable|string|max:500',
            'source_url'        => 'nullable',
            'tags'              => 'nullable|array',
            'tags.*'            => 'string|max:50',
            'is_featured'       => 'nullable|boolean',
            'is_visible'        => 'nullable|boolean',
            'sort_order'        => 'nullable|integer',
            'project_date'      => 'nullable|date',
        ]);

        // Handle image upload
        if ($request->hasFile('image')) {
            $validated['image'] = '/storage/' . $request->file('image')->store('portfolio/projects', 'public');
        }

        if ($request->hasFile('source_url')) {
            $validated['source_url'] = '/storage/' . $request->file('source_url')->store('portfolio/projects', 'public');
        }

        $project = $request->user()->portfolioProjects()->create($validated);

        return $this->success(new PortfolioProjectResource($project), 'Project berhasil ditambahkan', 201);
    }

    public function show(Request $request, PortfolioProject $portfolioProject)
    {
        $this->authorizeUser($request, $portfolioProject);
        return $this->success(new PortfolioProjectResource($portfolioProject));
    }

    public function update(Request $request, PortfolioProject $portfolioProject)
    {
        $this->authorizeUser($request, $portfolioProject);

        $validated = $request->validate([
            'title'             => 'sometimes|string|max:255',
            'description'       => 'nullable|string',
            'short_description' => 'nullable|string|max:255',
            'image'             => 'nullable|image|max:5120',
            'category'          => 'nullable|string|max:50',
            'demo_url'          => 'nullable|string|max:500',
            'source_url'        => 'nullable',
            'tags'              => 'nullable|array',
            'tags.*'            => 'string|max:50',
            'is_featured'       => 'nullable|boolean',
            'is_visible'        => 'nullable|boolean',
            'sort_order'        => 'nullable|integer',
            'project_date'      => 'nullable|date',
        ]);

        if ($request->hasFile('image')) {
            $validated['image'] = '/storage/' . $request->file('image')->store('portfolio/projects', 'public');
        }

        if ($request->hasFile('source_url')) {
            $validated['source_url'] = '/storage/' . $request->file('source_url')->store('portfolio/projects', 'public');
        }

        $portfolioProject->update($validated);

        return $this->success(new PortfolioProjectResource($portfolioProject), 'Project berhasil diperbarui');
    }

    public function destroy(Request $request, PortfolioProject $portfolioProject)
    {
        $this->authorizeUser($request, $portfolioProject);
        $portfolioProject->delete();
        return $this->success(null, 'Project berhasil dihapus');
    }

    /**
     * POST /api/portfolio/projects/{id}/screenshots — upload multiple screenshots
     */
    public function uploadScreenshots(Request $request, PortfolioProject $portfolioProject)
    {
        $this->authorizeUser($request, $portfolioProject);

        $request->validate([
            'screenshots'   => 'required|array|max:10',
            'screenshots.*' => 'image|max:5120',
        ]);

        $paths = $portfolioProject->screenshots ?? [];

        foreach ($request->file('screenshots') as $file) {
            $paths[] = '/storage/' . $file->store('portfolio/screenshots', 'public');
        }

        $portfolioProject->update(['screenshots' => $paths]);

        return $this->success(['screenshots' => $paths], 'Screenshots berhasil diupload');
    }

    private function authorizeUser(Request $request, PortfolioProject $project)
    {
        if ($project->user_id !== $request->user()->id) {
            abort(403, 'Unauthorized');
        }
    }
}
