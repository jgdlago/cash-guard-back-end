<?php

namespace App\Http\Requests;

use App\Enums\CategoryDirection;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'direction' => ['required', new Enum(CategoryDirection::class)],
            'color' => ['nullable', 'string', 'size:7'],
            'icon' => ['nullable', 'string', 'max:50'],
            'parent_id' => ['nullable', 'integer', 'exists:categories,id'],
        ];
    }
}
