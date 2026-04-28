<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\PaymentSource;
use App\Models\RecurringRule;
use App\Models\User;
use Database\Seeders\DefaultCategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RecurringRulesApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_a_recurring_rule(): void
    {
        $this->seed(DefaultCategorySeeder::class);
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $category = Category::query()->where('slug', 'assinaturas')->firstOrFail();
        $paymentSource = PaymentSource::create([
            'user_id' => $user->id,
            'type' => 'credit_card',
            'name' => 'Nubank',
            'currency_code' => 'BRL',
        ]);

        $response = $this->postJson('/api/v1/recurring-rules', [
            'payment_source_id' => $paymentSource->id,
            'category_id' => $category->id,
            'type' => 'expense',
            'frequency' => 'monthly',
            'status_on_generate' => 'pending',
            'amount' => '59,90',
            'description' => 'Streaming',
            'starts_on' => '2026-04-22',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.amount_cents', -5990)
            ->assertJsonPath('data.frequency', 'monthly')
            ->assertJsonPath('data.status_on_generate', 'pending');
    }

    public function test_it_lists_only_the_authenticated_users_rules(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        RecurringRule::create([
            'user_id' => $user->id,
            'type' => 'income',
            'frequency' => 'monthly',
            'status_on_generate' => 'posted',
            'amount_cents' => 100000,
            'currency_code' => 'BRL',
            'description' => 'Salary',
            'starts_on' => '2026-04-01',
            'next_run_on' => '2026-04-01',
            'is_active' => true,
        ]);

        RecurringRule::create([
            'user_id' => $otherUser->id,
            'type' => 'expense',
            'frequency' => 'monthly',
            'status_on_generate' => 'posted',
            'amount_cents' => -1000,
            'currency_code' => 'BRL',
            'description' => 'Other',
            'starts_on' => '2026-04-01',
            'next_run_on' => '2026-04-01',
            'is_active' => true,
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/recurring-rules');

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_it_updates_and_deactivates_recurring_rule(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $rule = RecurringRule::factory()->expense(5990)->create([
            'user_id' => $user->id,
            'description' => 'Streaming',
        ]);

        $update = $this->patchJson("/api/v1/recurring-rules/{$rule->id}", [
            'type' => 'expense',
            'amount' => '79,90',
            'description' => 'Streaming premium',
        ]);

        $update->assertOk()
            ->assertJsonPath('data.amount_cents', -7990)
            ->assertJsonPath('data.description', 'Streaming premium');

        $delete = $this->deleteJson("/api/v1/recurring-rules/{$rule->id}");
        $delete->assertNoContent();

        $this->assertDatabaseHas('recurring_rules', [
            'id' => $rule->id,
            'is_active' => false,
        ]);
    }

    public function test_recurring_rule_rejects_foreign_source_negative_amount_and_unknown_fields(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        Sanctum::actingAs($user);
        $foreignSource = PaymentSource::factory()->wallet()->create(['user_id' => $otherUser->id]);

        $response = $this->postJson('/api/v1/recurring-rules', [
            'payment_source_id' => $foreignSource->id,
            'type' => 'expense',
            'frequency' => 'monthly',
            'amount' => '-59,90',
            'description' => 'Streaming',
            'starts_on' => '2026-04-22',
            'unexpected' => 'blocked',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['payment_source_id', 'amount', 'unexpected']);
    }

    public function test_recurring_rule_precognition_does_not_create_rule(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->withPrecognition()->postJson('/api/v1/recurring-rules', [
            'type' => 'expense',
            'frequency' => 'monthly',
            'amount' => '59,90',
            'description' => 'Streaming',
            'starts_on' => '2026-04-22',
        ]);

        $response->assertSuccessfulPrecognition();
        $this->assertDatabaseMissing('recurring_rules', ['description' => 'Streaming']);
    }
}
