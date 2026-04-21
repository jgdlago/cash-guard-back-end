<?php

namespace App\Http\Requests;

use App\Enums\PaymentSourceType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StorePaymentSourceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', new Enum(PaymentSourceType::class)],
            'name' => ['required', 'string', 'max:120'],
            'currency_code' => ['nullable', 'string', 'size:3'],
            'parent_payment_source_id' => ['nullable', 'integer', 'exists:payment_sources,id'],
            'credit_limit' => ['nullable', 'regex:/^-?\d+([,.]\d{1,2})?$/'],
            'statement_closing_day' => ['nullable', 'integer', 'between:1,31'],
            'statement_due_day' => ['nullable', 'integer', 'between:1,31'],
            'is_active' => ['sometimes', 'boolean'],
            'display_order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
