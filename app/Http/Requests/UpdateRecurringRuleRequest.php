<?php

namespace App\Http\Requests;

use App\Enums\RecurringFrequency;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateRecurringRuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'payment_source_id' => ['sometimes', 'nullable', 'integer', 'exists:payment_sources,id'],
            'category_id' => ['sometimes', 'nullable', 'integer', 'exists:categories,id'],
            'type' => ['sometimes', new Enum(TransactionType::class)],
            'frequency' => ['sometimes', new Enum(RecurringFrequency::class)],
            'status_on_generate' => ['sometimes', new Enum(TransactionStatus::class)],
            'amount' => ['sometimes', 'regex:/^-?\d+([,.]\d{1,2})?$/'],
            'currency_code' => ['sometimes', 'string', 'size:3'],
            'description' => ['sometimes', 'string', 'max:255'],
            'notes' => ['sometimes', 'nullable', 'string'],
            'starts_on' => ['sometimes', 'date'],
            'next_run_on' => ['sometimes', 'date'],
            'ends_on' => ['sometimes', 'nullable', 'date'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
