<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'title'        => $this->title,
            'description'  => $this->description,
            'priority'     => $this->priority,
            'status'       => $this->status,
            'due_date'     => $this->due_date?->toDateString(),
            'due_time'     => $this->due_time,
            'is_pinned'    => $this->is_pinned,
            'sort_order'   => $this->sort_order,
            'is_overdue'   => $this->isOverdue(),
            'completed_at' => $this->completed_at?->toIso8601String(),
            'category'     => new CategoryResource($this->whenLoaded('category')),
            'created_at'   => $this->created_at->toIso8601String(),
            'updated_at'   => $this->updated_at->toIso8601String(),
        ];
    }
}
