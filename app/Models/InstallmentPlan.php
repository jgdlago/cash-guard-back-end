<?php

namespace App\Models;

use App\Casts\MoneyCast;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InstallmentPlan extends Model
{
    protected $fillable = [
        'user_id',
        'payment_source_id',
        'category_id',
        'description',
        'total_installments',
        'total_amount_cents',
        'currency_code',
        'first_due_date',
    ];

    protected function casts(): array
    {
        return [
            'total_amount_cents' => MoneyCast::class,
            'first_due_date' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function paymentSource(): BelongsTo
    {
        return $this->belongsTo(PaymentSource::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }
}
