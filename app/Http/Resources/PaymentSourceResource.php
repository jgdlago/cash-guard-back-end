<?php

namespace App\Http\Resources;

use App\Support\Money;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentSourceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type->value,
            'name' => $this->name,
            'currency_code' => $this->currency_code,
            'parent_payment_source_id' => $this->parent_payment_source_id,
            'credit_limit_cents' => $this->credit_limit_cents,
            'credit_limit' => Money::formatFromCents($this->credit_limit_cents),
            'statement_closing_day' => $this->statement_closing_day,
            'statement_due_day' => $this->statement_due_day,
            'is_active' => $this->is_active,
            'display_order' => $this->display_order,
        ];
    }
}
