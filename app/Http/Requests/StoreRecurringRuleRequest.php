<?php

namespace App\Http\Requests;

use App\Enums\RecurringFrequency;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Http\Requests\Concerns\ValidatesFinancialOwnership;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreRecurringRuleRequest extends FormRequest
{
    use ValidatesFinancialOwnership;

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'payment_source_id' => ['nullable', 'integer', $this->ownedPaymentSourceRule()],
            'category_id' => ['nullable', 'integer', $this->visibleCategoryRule()],
            'type' => ['required', new Enum(TransactionType::class)],
            'frequency' => ['required', new Enum(RecurringFrequency::class)],
            'status_on_generate' => ['sometimes', new Enum(TransactionStatus::class)],
            'amount' => ['required', 'regex:/^\d+([,.]\d{1,2})?$/'],
            'currency_code' => ['nullable', 'string', 'size:3', 'uppercase'],
            'description' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'starts_on' => ['required', 'date'],
            'next_run_on' => ['nullable', 'date'],
            'ends_on' => ['nullable', 'date', 'after_or_equal:starts_on'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
