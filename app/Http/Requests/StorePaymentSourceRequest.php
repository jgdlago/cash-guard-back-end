<?php

namespace App\Http\Requests;

use App\Enums\PaymentSourceType;
use App\Http\Requests\Concerns\ValidatesFinancialOwnership;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StorePaymentSourceRequest extends FormRequest
{
    use ValidatesFinancialOwnership;

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', new Enum(PaymentSourceType::class)],
            'name' => ['required', 'string', 'max:120'],
            'currency_code' => ['nullable', 'string', 'size:3', 'uppercase'],
            'parent_payment_source_id' => ['nullable', 'integer', $this->ownedPaymentSourceRule()],
            'credit_limit' => ['nullable', 'regex:/^\d+([,.]\d{1,2})?$/'],
            'statement_closing_day' => ['nullable', 'integer', 'between:1,31'],
            'statement_due_day' => ['nullable', 'integer', 'between:1,31'],
            'is_active' => ['sometimes', 'boolean'],
            'display_order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
