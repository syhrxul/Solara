<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HabitLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'habit_id'    => $this->habit_id,
            'logged_date' => $this->logged_date?->toDateString(),
            'count'       => $this->count,
            'completed'   => $this->completed,
            'notes'       => $this->notes,
            'created_at'  => $this->created_at->toIso8601String(),
        ];
    }
}
