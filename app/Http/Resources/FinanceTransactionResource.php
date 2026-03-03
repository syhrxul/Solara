<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FinanceTransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'title'            => $this->title,
            'description'      => $this->description,
            'amount'           => (float) $this->amount,
            'type'             => $this->type,
            'payment_method'   => $this->payment_method,
            'transaction_date' => $this->transaction_date?->toDateString(),
            'is_recurring'     => $this->is_recurring,
            'recurring_period' => $this->recurring_period,
            'category'         => new CategoryResource($this->whenLoaded('category')),
            'bank'             => new CategoryResource($this->whenLoaded('bank')),
            'created_at'       => $this->created_at->toIso8601String(),
            'updated_at'       => $this->updated_at->toIso8601String(),
        ];
    }
}
