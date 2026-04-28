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

    public function test_it_does_not_duplicate_existing_generated_transaction(): void
    {
        $user = User::factory()->create();

        $rule = RecurringRule::factory()->expense(3200)->create([
            'user_id' => $user->id,
            'description' => 'Internet',
            'starts_on' => '2026-04-01',
            'next_run_on' => '2026-04-01',
        ]);

        $rule->transactions()->create([
            'user_id' => $user->id,
            'type' => 'expense',
            'status' => 'posted',
            'amount_cents' => -3200,
            'currency_code' => 'BRL',
            'transaction_date' => '2026-04-01',
            'description' => 'Internet',
            'posted_at' => now(),
        ]);

        $this->artisan('finance:process-recurring-rules', ['--date' => '2026-04-01'])
            ->assertExitCode(0);

        $this->assertDatabaseCount('transactions', 1);
        $this->assertDatabaseHas('recurring_rules', [
            'id' => $rule->id,
            'next_run_on' => '2026-05-01',
        ]);
    }

    public function test_it_deactivates_rule_after_end_date(): void
    {
        $user = User::factory()->create();

        $rule = RecurringRule::factory()->expense(3200)->create([
            'user_id' => $user->id,
            'starts_on' => '2026-04-01',
            'next_run_on' => '2026-04-01',
            'ends_on' => '2026-04-01',
        ]);

        $this->artisan('finance:process-recurring-rules', ['--date' => '2026-05-01'])
            ->assertExitCode(0);

        $this->assertDatabaseHas('recurring_rules', [
            'id' => $rule->id,
            'is_active' => false,
        ]);
    }
}
