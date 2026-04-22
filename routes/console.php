<?php

use App\Enums\RecurringFrequency;
use App\Models\RecurringRule;
use App\Models\Transaction;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('finance:process-recurring-rules {--date=}', function () {
    $runDate = $this->option('date')
        ? Carbon::parse($this->option('date'))->startOfDay()
        : now()->startOfDay();

    $rules = RecurringRule::query()
        ->where('is_active', true)
        ->whereDate('next_run_on', '<=', $runDate->toDateString())
        ->where(function ($query) use ($runDate): void {
            $query->whereNull('ends_on')
                ->orWhereDate('ends_on', '>=', $runDate->toDateString());
        })
        ->get();

    $createdTransactions = 0;

    foreach ($rules as $rule) {
        DB::transaction(function () use ($rule, $runDate, &$createdTransactions): void {
            $nextRunOn = $rule->next_run_on->copy();

            while ($nextRunOn->lte($runDate)) {
                if ($rule->ends_on !== null && $nextRunOn->gt($rule->ends_on)) {
                    $rule->update([
                        'is_active' => false,
                        'last_processed_at' => now(),
                    ]);

                    return;
                }

                $exists = Transaction::query()
                    ->where('recurring_rule_id', $rule->id)
                    ->whereDate('transaction_date', $nextRunOn->toDateString())
                    ->exists();

                if (! $exists) {
                    Transaction::create([
                        'user_id' => $rule->user_id,
                        'payment_source_id' => $rule->payment_source_id,
                        'category_id' => $rule->category_id,
                        'recurring_rule_id' => $rule->id,
                        'type' => $rule->type,
                        'status' => $rule->status_on_generate,
                        'amount_cents' => $rule->amount_cents,
                        'currency_code' => $rule->currency_code,
                        'transaction_date' => $nextRunOn->toDateString(),
                        'due_date' => $nextRunOn->toDateString(),
                        'posted_at' => $rule->status_on_generate->value === 'posted' ? now() : null,
                        'description' => $rule->description,
                        'notes' => $rule->notes,
                    ]);

                    $createdTransactions++;
                }

                $nextRunOn = match ($rule->frequency) {
                    RecurringFrequency::Daily => $nextRunOn->addDay(),
                    RecurringFrequency::Weekly => $nextRunOn->addWeek(),
                    RecurringFrequency::Monthly => $nextRunOn->addMonthNoOverflow(),
                    RecurringFrequency::Yearly => $nextRunOn->addYearNoOverflow(),
                };
            }

            $rule->update([
                'next_run_on' => $nextRunOn->toDateString(),
                'last_processed_at' => now(),
                'is_active' => $rule->ends_on === null || $nextRunOn->lte($rule->ends_on),
            ]);
        });
    }

    $this->info("Processed {$rules->count()} recurring rules and created {$createdTransactions} transactions.");
})->purpose('Generate due transactions from recurring rules');
