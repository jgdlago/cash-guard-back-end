<?php

namespace App\Http\Requests;

use App\Enums\CategoryDirection;
use App\Http\Requests\Concerns\ValidatesFinancialOwnership;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreCategoryRequest extends FormRequest
{
    use ValidatesFinancialOwnership;

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'direction' => ['required', new Enum(CategoryDirection::class)],
            'color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'icon' => ['nullable', 'string', 'alpha_dash', 'max:50'],
            'parent_id' => ['nullable', 'integer', $this->visibleCategoryRule()],
        ];
    }
}
