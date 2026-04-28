<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesFinancialOwnership;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreInstallmentPlanRequest extends FormRequest
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
            'description' => ['required', 'string', 'max:255'],
            'currency_code' => ['nullable', 'string', 'size:3', 'uppercase'],
            'transaction_date' => ['required', 'date'],
            'installments' => ['required', 'array', 'min:1'],
            'installments.*.number' => ['required', 'integer', 'min:1'],
            'installments.*.amount' => ['required', 'regex:/^\d+([,.]\d{1,2})?$/'],
            'installments.*.due_date' => ['required', 'date'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->has('installments') || ! is_array($this->input('installments'))) {
                    return;
                }

                $numbers = collect($this->input('installments'))
                    ->pluck('number')
                    ->map(fn (mixed $number) => (int) $number)
                    ->sort()
                    ->values();

                $expected = collect(range(1, count($this->input('installments'))));

                if ($numbers->all() !== $expected->all()) {
                    $validator->errors()->add('installments', 'As parcelas devem ser sequenciais a partir de 1.');
                }
            },
        ];
    }
}
