<?php

namespace App\Http\Requests;

use App\Enums\RecurringFrequency;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Http\Requests\Concerns\ValidatesFinancialOwnership;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateRecurringRuleRequest extends FormRequest
{
    use ValidatesFinancialOwnership;

    public function authorize(): bool
    {
        $recurringRule = $this->route('recurringRule');

        return $this->user() !== null
            && $recurringRule !== null
            && $recurringRule->user_id === $this->user()->id;
    }

    public function rules(): array
    {
        return [
            'payment_source_id' => ['sometimes', 'nullable', 'integer', $this->ownedPaymentSourceRule()],
            'category_id' => ['sometimes', 'nullable', 'integer', $this->visibleCategoryRule()],
            'type' => ['sometimes', new Enum(TransactionType::class)],
            'frequency' => ['sometimes', new Enum(RecurringFrequency::class)],
            'status_on_generate' => ['sometimes', new Enum(TransactionStatus::class)],
            'amount' => ['sometimes', 'regex:/^\d+([,.]\d{1,2})?$/'],
            'currency_code' => ['sometimes', 'string', 'size:3', 'uppercase'],
            'description' => ['sometimes', 'string', 'max:255'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'starts_on' => ['sometimes', 'date'],
            'next_run_on' => ['sometimes', 'date'],
            'ends_on' => ['sometimes', 'nullable', 'date', 'after_or_equal:starts_on'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
