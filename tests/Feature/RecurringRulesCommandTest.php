<?php

namespace Tests\Feature;

use App\Models\RecurringRule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecurringRulesCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_generates_transactions_from_due_recurring_rules(): void
    {
        $user = User::factory()->create();

        $rule = RecurringRule::create([
            'user_id' => $user->id,
            'type' => 'expense',
            'frequency' => 'monthly',
            'status_on_generate' => 'posted',
            'amount_cents' => -3200,
            'currency_code' => 'BRL',
            'description' => 'Internet',
            'starts_on' => '2026-04-01',
            'next_run_on' => '2026-04-01',
            'is_active' => true,
        ]);

        $this->artisan('finance:process-recurring-rules', ['--date' => '2026-04-22'])
            ->assertExitCode(0);

        $this->assertDatabaseHas('transactions', [
            'recurring_rule_id' => $rule->id,
            'description' => 'Internet',
            'amount_cents' => -3200,
            'transaction_date' => '2026-04-01',
        ]);

        $this->assertDatabaseHas('recurring_rules', [
            'id' => $rule->id,
            'next_run_on' => '2026-05-01',
        ]);
    }
}
