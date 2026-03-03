<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GoalMilestoneResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'goal_id'      => $this->goal_id,
            'title'        => $this->title,
            'description'  => $this->description,
            'is_completed' => $this->is_completed,
            'target_date'  => $this->target_date?->toDateString(),
            'completed_at' => $this->completed_at?->toIso8601String(),
            'sort_order'   => $this->sort_order,
        ];
    }
}
