<?php

namespace App\Http\Requests;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Http\Requests\Concerns\ValidatesFinancialOwnership;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreTransactionRequest extends FormRequest
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
            'status' => ['sometimes', new Enum(TransactionStatus::class)],
            'amount' => ['required', 'regex:/^\d+([,.]\d{1,2})?$/'],
            'currency_code' => ['nullable', 'string', 'size:3', 'uppercase'],
            'transaction_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date'],
            'description' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
