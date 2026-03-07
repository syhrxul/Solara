<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'    => $this->id,
            'name'  => $this->name,
            'color' => $this->color,
            'icon'  => $this->icon,
            'type'  => $this->type,
            'balance' => $this->type === 'bank' ? (float)($this->total_income ?? 0) - (float)($this->total_expense ?? 0) : null,
            'total_income' => $this->type === 'bank' ? (float)($this->total_income ?? 0) : null,
            'total_expense' => $this->type === 'bank' ? (float)($this->total_expense ?? 0) : null,
            'transaction_count' => $this->type === 'bank' ? (int)($this->transaction_count ?? 0) : null,
        ];
    }
}
