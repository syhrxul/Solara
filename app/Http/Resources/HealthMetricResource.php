<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HealthMetricResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'date'       => $this->date?->toDateString(),
            'type'       => $this->type,
            'value'      => (float) $this->value,
            'details'    => $this->details,
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
