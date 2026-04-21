<?php

namespace App\Http\Resources;

use App\Support\Money;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InstallmentPlanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'description' => $this->description,
            'total_installments' => $this->total_installments,
            'total_amount_cents' => $this->total_amount_cents,
            'total_amount' => Money::formatFromCents($this->total_amount_cents),
            'currency_code' => $this->currency_code,
            'first_due_date' => $this->first_due_date?->toDateString(),
            'payment_source_id' => $this->payment_source_id,
            'category_id' => $this->category_id,
            'transactions' => TransactionResource::collection($this->whenLoaded('transactions')),
        ];
    }
}
