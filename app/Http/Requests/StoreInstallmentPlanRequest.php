<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreInstallmentPlanRequest extends FormRequest
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
            'description' => ['required', 'string', 'max:255'],
            'currency_code' => ['nullable', 'string', 'size:3'],
            'transaction_date' => ['required', 'date'],
            'installments' => ['required', 'array', 'min:1'],
            'installments.*.number' => ['required', 'integer', 'min:1'],
            'installments.*.amount' => ['required', 'regex:/^-?\d+([,.]\d{1,2})?$/'],
            'installments.*.due_date' => ['required', 'date'],
        ];
    }
}
