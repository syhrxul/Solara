<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\PortfolioProfile;
use Illuminate\Http\Request;

class PortfolioProfileController extends Controller
{
    use ApiResponse;

    /**
     * GET /api/portfolio/profile — get current user's portfolio profile
     */
    public function show(Request $request)
    {
        $profile = $request->user()->portfolioProfile;

        if (!$profile) {
            $profile = $request->user()->portfolioProfile()->create([
                'name' => $request->user()->name,
                'email' => $request->user()->email,
            ]);
        }

        return $this->success($profile);
    }

    /**
     * PUT /api/portfolio/profile — update profile
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'name'           => 'sometimes|string|max:255',
            'tagline'        => 'nullable|string|max:255',
            'bio'            => 'nullable|string',
            'location'       => 'nullable|string|max:255',
            'avatar'         => 'nullable|string|max:500',
            'resume_url'     => 'nullable|string|max:500',
            'email'          => 'nullable|email|max:255',
            'github_url'     => 'nullable|string|max:500',
            'linkedin_url'   => 'nullable|string|max:500',
            'website_url'    => 'nullable|string|max:500',
            'twitter_url'    => 'nullable|string|max:500',
            'instagram_url'  => 'nullable|string|max:500',
            'skills'         => 'nullable|array',
            'skills.*'       => 'string|max:50',
            'is_open_to_work' => 'nullable|boolean',
        ]);

        $profile = $request->user()->portfolioProfile;

        if (!$profile) {
            $profile = $request->user()->portfolioProfile()->create($validated);
        } else {
            $profile->update($validated);
        }

        return $this->success($profile, 'Profil portfolio berhasil diperbarui');
    }

    /**
     * POST /api/portfolio/profile/avatar — upload avatar
     */
    public function uploadAvatar(Request $request)
    {
        $request->validate([
            'avatar' => 'required|image|max:2048',
        ]);

        $path = $request->file('avatar')->store('portfolio/avatars', 'public');

        $profile = $request->user()->portfolioProfile;
        if (!$profile) {
            $profile = $request->user()->portfolioProfile()->create([
                'avatar' => '/storage/' . $path,
            ]);
        } else {
            $profile->update(['avatar' => '/storage/' . $path]);
        }

        return $this->success(['avatar' => '/storage/' . $path], 'Avatar berhasil diupload');
    }
}
