<?php

namespace App\Models;

use App\Casts\MoneyCast;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'payment_source_id',
        'category_id',
        'installment_plan_id',
        'recurring_rule_id',
        'type',
        'status',
        'amount_cents',
        'currency_code',
        'transaction_date',
        'due_date',
        'posted_at',
        'description',
        'notes',
        'installment_number',
        'total_installments',
        'competence_month',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'amount_cents' => MoneyCast::class,
            'transaction_date' => 'date',
            'due_date' => 'date',
            'posted_at' => 'datetime',
            'competence_month' => 'date',
            'cancelled_at' => 'datetime',
            'type' => TransactionType::class,
            'status' => TransactionStatus::class,
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

    public function installmentPlan(): BelongsTo
    {
        return $this->belongsTo(InstallmentPlan::class);
    }

    public function recurringRule(): BelongsTo
    {
        return $this->belongsTo(RecurringRule::class);
    }
}
