<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HabitResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'name'                => $this->name,
            'description'         => $this->description,
            'icon'                => $this->icon,
            'color'               => $this->color,
            'frequency'           => $this->frequency,
            'frequency_days'      => $this->frequency_days,
            'target_count'        => $this->target_count,
            'unit'                => $this->unit,
            'reminder_time'       => $this->reminder_time,
            'is_active'           => $this->is_active,
            'current_streak'      => $this->current_streak,
            'longest_streak'      => $this->longest_streak,
            'last_completed_date' => $this->last_completed_date?->toDateString(),
            'started_at'          => $this->started_at?->toDateString(),
            'completed_today'     => $this->isCompletedToday(),
            'should_do_today'     => $this->shouldDoOnDay(),
            'can_restore_streak'  => $this->canRestoreStreak(),
            'completion_rate'     => $this->completionRate(),
            'created_at'          => $this->created_at->toIso8601String(),
            'updated_at'          => $this->updated_at->toIso8601String(),
        ];
    }
}
