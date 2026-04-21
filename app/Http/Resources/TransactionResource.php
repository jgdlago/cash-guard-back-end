<?php

namespace App\Http\Resources;

use App\Support\Money;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type->value,
            'status' => $this->status->value,
            'amount_cents' => $this->amount_cents,
            'amount' => Money::formatFromCents($this->amount_cents),
            'currency_code' => $this->currency_code,
            'transaction_date' => $this->transaction_date?->toDateString(),
            'due_date' => $this->due_date?->toDateString(),
            'description' => $this->description,
            'notes' => $this->notes,
            'payment_source_id' => $this->payment_source_id,
            'category_id' => $this->category_id,
            'installment_plan_id' => $this->installment_plan_id,
            'installment_number' => $this->installment_number,
            'total_installments' => $this->total_installments,
        ];
    }
}
