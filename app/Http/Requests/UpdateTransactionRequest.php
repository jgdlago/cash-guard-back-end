<?php

namespace App\Http\Requests;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateTransactionRequest extends FormRequest
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
            'status' => ['sometimes', new Enum(TransactionStatus::class)],
            'amount' => ['sometimes', 'regex:/^-?\d+([,.]\d{1,2})?$/'],
            'currency_code' => ['sometimes', 'string', 'size:3'],
            'transaction_date' => ['sometimes', 'date'],
            'due_date' => ['sometimes', 'nullable', 'date'],
            'description' => ['sometimes', 'string', 'max:255'],
            'notes' => ['sometimes', 'nullable', 'string'],
        ];
    }
}
