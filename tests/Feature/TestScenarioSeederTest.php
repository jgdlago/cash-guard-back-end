<?php

namespace Tests\Feature;

use App\Models\FinancialAuditLog;
use App\Models\InstallmentPlan;
use App\Models\PaymentSource;
use App\Models\RecurringRule;
use App\Models\Transaction;
use App\Models\User;
use Database\Seeders\TestScenarioSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TestScenarioSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_seeds_a_complete_financial_scenario(): void
    {
        $this->seed(TestScenarioSeeder::class);

        $user = User::query()->where('email', 'scenario@example.com')->firstOrFail();

        $this->assertDatabaseHas('payment_sources', ['user_id' => $user->id, 'name' => 'Carteira principal']);
        $this->assertSame(2, PaymentSource::query()->where('user_id', $user->id)->count());
        $this->assertSame(4, Transaction::query()->where('user_id', $user->id)->count());
        $this->assertSame(1, InstallmentPlan::query()->where('user_id', $user->id)->count());
        $this->assertSame(1, RecurringRule::query()->where('user_id', $user->id)->count());
        $this->assertSame(1, FinancialAuditLog::query()->where('user_id', $user->id)->count());
    }
}
