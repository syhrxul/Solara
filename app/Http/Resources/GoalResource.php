<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GoalResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'title'        => $this->title,
            'description'  => $this->description,
            'status'       => $this->status,
            'progress'     => $this->progress,
            'target_date'  => $this->target_date?->toDateString(),
            'icon'         => $this->icon,
            'color'        => $this->color,
            'is_pinned'    => $this->is_pinned,
            'completed_at' => $this->completed_at?->toIso8601String(),
            'category'     => new CategoryResource($this->whenLoaded('category')),
            'milestones'   => GoalMilestoneResource::collection($this->whenLoaded('milestones')),
            'created_at'   => $this->created_at->toIso8601String(),
            'updated_at'   => $this->updated_at->toIso8601String(),
        ];
    }
}
