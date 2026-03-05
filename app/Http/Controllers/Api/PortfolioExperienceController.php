<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\PortfolioExperience;
use Illuminate\Http\Request;

class PortfolioExperienceController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $experiences = $request->user()->portfolioExperiences()
            ->orderBy('sort_order')
            ->orderByDesc('start_date')
            ->get();

        return $this->success($experiences);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'        => 'required|string|max:255',
            'company'      => 'required|string|max:255',
            'company_logo' => 'nullable|image|max:2048',
            'location'     => 'nullable|string|max:255',
            'type'         => 'nullable|in:full_time,part_time,freelance,internship,contract',
            'description'  => 'nullable|string',
            'tech_stack'   => 'nullable|array',
            'tech_stack.*' => 'string|max:50',
            'start_date'   => 'required|date',
            'end_date'     => 'nullable|date',
            'is_current'   => 'nullable|boolean',
            'is_visible'   => 'nullable|boolean',
            'sort_order'   => 'nullable|integer',
        ]);

        if ($request->hasFile('company_logo')) {
            $validated['company_logo'] = '/storage/' . $request->file('company_logo')->store('portfolio/companies', 'public');
        }

        $exp = $request->user()->portfolioExperiences()->create($validated);

        return $this->success($exp, 'Pengalaman berhasil ditambahkan', 201);
    }

    public function update(Request $request, PortfolioExperience $portfolioExperience)
    {
        if ($portfolioExperience->user_id !== $request->user()->id) {
            abort(403, 'Unauthorized');
        }

        $validated = $request->validate([
            'title'        => 'sometimes|string|max:255',
            'company'      => 'sometimes|string|max:255',
            'company_logo' => 'nullable|image|max:2048',
            'location'     => 'nullable|string|max:255',
            'type'         => 'nullable|in:full_time,part_time,freelance,internship,contract',
            'description'  => 'nullable|string',
            'tech_stack'   => 'nullable|array',
            'tech_stack.*' => 'string|max:50',
            'start_date'   => 'sometimes|date',
            'end_date'     => 'nullable|date',
            'is_current'   => 'nullable|boolean',
            'is_visible'   => 'nullable|boolean',
            'sort_order'   => 'nullable|integer',
        ]);

        if ($request->hasFile('company_logo')) {
            $validated['company_logo'] = '/storage/' . $request->file('company_logo')->store('portfolio/companies', 'public');
        }

        $portfolioExperience->update($validated);

        return $this->success($portfolioExperience, 'Pengalaman berhasil diperbarui');
    }

    public function destroy(Request $request, PortfolioExperience $portfolioExperience)
    {
        if ($portfolioExperience->user_id !== $request->user()->id) {
            abort(403, 'Unauthorized');
        }

        $portfolioExperience->delete();
        return $this->success(null, 'Pengalaman berhasil dihapus');
    }
}
