<?php

namespace App\Http\Requests;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Http\Requests\Concerns\ValidatesFinancialOwnership;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateTransactionRequest extends FormRequest
{
    use ValidatesFinancialOwnership;

    public function authorize(): bool
    {
        $transaction = $this->route('transaction');

        return $this->user() !== null
            && $transaction !== null
            && $transaction->user_id === $this->user()->id;
    }

    public function rules(): array
    {
        return [
            'payment_source_id' => ['sometimes', 'nullable', 'integer', $this->ownedPaymentSourceRule()],
            'category_id' => ['sometimes', 'nullable', 'integer', $this->visibleCategoryRule()],
            'type' => ['sometimes', new Enum(TransactionType::class)],
            'status' => ['sometimes', new Enum(TransactionStatus::class)],
            'amount' => ['sometimes', 'regex:/^\d+([,.]\d{1,2})?$/'],
            'currency_code' => ['sometimes', 'string', 'size:3', 'uppercase'],
            'transaction_date' => ['sometimes', 'date'],
            'due_date' => ['sometimes', 'nullable', 'date'],
            'description' => ['sometimes', 'string', 'max:255'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:2000'],
        ];
    }
}
