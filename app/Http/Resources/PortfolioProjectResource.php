<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PortfolioProjectResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'short_description' => $this->short_description,
            'image' => $this->image,
            'category' => $this->category,
            'demo_url' => $this->demo_url,
            'source_url' => $this->source_url,
            'tags' => $this->tags ?? [],
            'screenshots' => $this->screenshots ?? [],
            'is_featured' => $this->is_featured,
            'is_visible' => $this->is_visible,
            'sort_order' => $this->sort_order,
            'project_date' => $this->project_date?->format('Y-m-d'),
            'created_at' => $this->created_at->toISOString(),
        ];
    }
}
