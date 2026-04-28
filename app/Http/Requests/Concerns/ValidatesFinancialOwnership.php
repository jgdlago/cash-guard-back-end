<?php

namespace App\Http\Requests\Concerns;

use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;

trait ValidatesFinancialOwnership
{
    protected function ownedPaymentSourceRule(): Exists
    {
        return Rule::exists('payment_sources', 'id')
            ->where('user_id', $this->user()?->id);
    }

    protected function visibleCategoryRule(): Exists
    {
        $userId = $this->user()?->id;

        return Rule::exists('categories', 'id')
            ->where(fn ($query) => $query
                ->whereNull('user_id')
                ->orWhere('user_id', $userId));
    }
}
