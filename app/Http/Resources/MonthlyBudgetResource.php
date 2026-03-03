<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MonthlyBudgetResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'month_year' => $this->month_year,
            'amount'     => (float) $this->amount,
            'spent'      => (float) $this->spent,
            'remaining'  => (float) ($this->amount - $this->spent),
            'percentage' => $this->amount > 0 ? round(($this->spent / $this->amount) * 100, 1) : 0,
            'notes'      => $this->notes,
            'category'   => new CategoryResource($this->whenLoaded('category')),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
