<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PortfolioCertificateResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'issuer' => $this->issuer,
            'credential_id' => $this->credential_id,
            'credential_url' => $this->credential_url,
            'image' => $this->image,
            'issued_date' => $this->issued_date?->format('Y-m-d'),
            'expiry_date' => $this->expiry_date?->format('Y-m-d'),
            'tags' => $this->tags ?? [],
            'is_visible' => $this->is_visible,
            'sort_order' => $this->sort_order,
            'created_at' => $this->created_at->toISOString(),
        ];
    }
}
