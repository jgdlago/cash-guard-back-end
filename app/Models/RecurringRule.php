<?php

namespace App\Models;

use App\Casts\MoneyCast;
use App\Enums\RecurringFrequency;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RecurringRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'payment_source_id',
        'category_id',
        'type',
        'frequency',
        'status_on_generate',
        'amount_cents',
        'currency_code',
        'description',
        'notes',
        'starts_on',
        'next_run_on',
        'ends_on',
        'is_active',
        'last_processed_at',
    ];

    protected function casts(): array
    {
        return [
            'amount_cents' => MoneyCast::class,
            'starts_on' => 'date',
            'next_run_on' => 'date',
            'ends_on' => 'date',
            'last_processed_at' => 'datetime',
            'is_active' => 'boolean',
            'type' => TransactionType::class,
            'frequency' => RecurringFrequency::class,
            'status_on_generate' => TransactionStatus::class,
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
