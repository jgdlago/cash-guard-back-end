<?php

namespace App\Models;

use App\Casts\MoneyCast;
use App\Enums\PaymentSourceType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentSource extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'name',
        'currency_code',
        'parent_payment_source_id',
        'credit_limit_cents',
        'statement_closing_day',
        'statement_due_day',
        'is_active',
        'display_order',
    ];

    protected function casts(): array
    {
        return [
            'credit_limit_cents' => MoneyCast::class,
            'is_active' => 'boolean',
            'type' => PaymentSourceType::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_payment_source_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_payment_source_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }
}
