<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpsertCategoryUserPreferenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'is_hidden' => ['sometimes', 'boolean'],
            'display_order_override' => ['sometimes', 'nullable', 'integer', 'min:0'],
        ];
    }
}
