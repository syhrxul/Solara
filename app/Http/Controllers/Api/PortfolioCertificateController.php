<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PortfolioCertificateResource;
use App\Http\Traits\ApiResponse;
use App\Models\PortfolioCertificate;
use Illuminate\Http\Request;

class PortfolioCertificateController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $certs = $request->user()->portfolioCertificates()
            ->orderBy('sort_order')
            ->orderByDesc('issued_date')
            ->get();

        return PortfolioCertificateResource::collection($certs)
            ->additional(['success' => true]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'          => 'required|string|max:255',
            'issuer'         => 'nullable|string|max:255',
            'credential_id'  => 'nullable|string|max:255',
            'credential_url' => 'nullable|string|max:500',
            'image'          => 'nullable|image|max:5120',
            'issued_date'    => 'nullable|date',
            'expiry_date'    => 'nullable|date',
            'tags'           => 'nullable|array',
            'tags.*'         => 'string|max:50',
            'is_visible'     => 'nullable|boolean',
            'sort_order'     => 'nullable|integer',
        ]);

        if ($request->hasFile('image')) {
            $validated['image'] = '/storage/' . $request->file('image')->store('portfolio/certificates', 'public');
        }

        $cert = $request->user()->portfolioCertificates()->create($validated);

        return $this->success(new PortfolioCertificateResource($cert), 'Sertifikat berhasil ditambahkan', 201);
    }

    public function show(Request $request, PortfolioCertificate $portfolioCertificate)
    {
        $this->authorizeUser($request, $portfolioCertificate);
        return $this->success(new PortfolioCertificateResource($portfolioCertificate));
    }

    public function update(Request $request, PortfolioCertificate $portfolioCertificate)
    {
        $this->authorizeUser($request, $portfolioCertificate);

        $validated = $request->validate([
            'title'          => 'sometimes|string|max:255',
            'issuer'         => 'nullable|string|max:255',
            'credential_id'  => 'nullable|string|max:255',
            'credential_url' => 'nullable|string|max:500',
            'image'          => 'nullable|image|max:5120',
            'issued_date'    => 'nullable|date',
            'expiry_date'    => 'nullable|date',
            'tags'           => 'nullable|array',
            'tags.*'         => 'string|max:50',
            'is_visible'     => 'nullable|boolean',
            'sort_order'     => 'nullable|integer',
        ]);

        if ($request->hasFile('image')) {
            $validated['image'] = '/storage/' . $request->file('image')->store('portfolio/certificates', 'public');
        }

        $portfolioCertificate->update($validated);

        return $this->success(new PortfolioCertificateResource($portfolioCertificate), 'Sertifikat berhasil diperbarui');
    }

    public function destroy(Request $request, PortfolioCertificate $portfolioCertificate)
    {
        $this->authorizeUser($request, $portfolioCertificate);
        $portfolioCertificate->delete();
        return $this->success(null, 'Sertifikat berhasil dihapus');
    }

    private function authorizeUser(Request $request, PortfolioCertificate $cert)
    {
        if ($cert->user_id !== $request->user()->id) {
            abort(403, 'Unauthorized');
        }
    }
}
