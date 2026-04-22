<?php

namespace App\Http\Resources;

use App\Support\Money;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RecurringRuleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type->value,
            'frequency' => $this->frequency->value,
            'status_on_generate' => $this->status_on_generate->value,
            'amount_cents' => $this->amount_cents,
            'amount' => Money::formatFromCents($this->amount_cents),
            'currency_code' => $this->currency_code,
            'description' => $this->description,
            'notes' => $this->notes,
            'starts_on' => $this->starts_on?->toDateString(),
            'next_run_on' => $this->next_run_on?->toDateString(),
            'ends_on' => $this->ends_on?->toDateString(),
            'is_active' => $this->is_active,
            'payment_source_id' => $this->payment_source_id,
            'category_id' => $this->category_id,
            'last_processed_at' => $this->last_processed_at?->toAtomString(),
            'transactions_count' => $this->whenCounted('transactions'),
        ];
    }
}
