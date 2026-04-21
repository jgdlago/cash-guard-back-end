<?php

namespace App\Http\Requests;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'payment_source_id' => ['nullable', 'integer', 'exists:payment_sources,id'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'type' => ['required', new Enum(TransactionType::class)],
            'status' => ['sometimes', new Enum(TransactionStatus::class)],
            'amount' => ['required', 'regex:/^-?\d+([,.]\d{1,2})?$/'],
            'currency_code' => ['nullable', 'string', 'size:3'],
            'transaction_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date'],
            'description' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
