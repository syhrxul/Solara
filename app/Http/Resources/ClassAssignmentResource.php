<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClassAssignmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'title'             => $this->title,
            'description'       => $this->description,
            'type'              => $this->type,
            'status'            => $this->status,
            'deadline'          => $this->deadline?->toDateString(),
            'nilai'             => $this->nilai,
            'is_overdue'        => $this->isOverdue(),
            'class_schedule_id' => $this->class_schedule_id,
            'class_schedule'    => new ClassScheduleResource($this->whenLoaded('classSchedule')),
            'created_at'        => $this->created_at->toIso8601String(),
        ];
    }
}
