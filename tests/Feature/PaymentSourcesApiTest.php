<?php

namespace Tests\Feature;

use App\Models\PaymentSource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PaymentSourcesApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_and_lists_payment_sources_for_authenticated_user(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        Sanctum::actingAs($user);

        PaymentSource::factory()->wallet()->create(['user_id' => $otherUser->id, 'name' => 'Other wallet']);

        $response = $this->postJson('/api/v1/payment-sources', [
            'type' => 'credit_card',
            'name' => 'Nubank',
            'credit_limit' => '2500,00',
            'statement_closing_day' => 10,
            'statement_due_day' => 17,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.name', 'Nubank')
            ->assertJsonPath('data.credit_limit_cents', 250000);

        $list = $this->getJson('/api/v1/payment-sources');
        $list->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Nubank');
    }

    public function test_it_rejects_foreign_parent_negative_credit_limit_and_unknown_fields(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        Sanctum::actingAs($user);

        $foreignParent = PaymentSource::factory()->bankAccount()->create(['user_id' => $otherUser->id]);

        $response = $this->postJson('/api/v1/payment-sources', [
            'type' => 'credit_card',
            'name' => 'Nubank',
            'parent_payment_source_id' => $foreignParent->id,
            'credit_limit' => '-100,00',
            'statement_closing_day' => 35,
            'unexpected' => 'blocked',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors([
                'parent_payment_source_id',
                'credit_limit',
                'statement_closing_day',
                'unexpected',
            ]);
    }

    public function test_payment_source_precognition_does_not_create_record(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->withPrecognition()->postJson('/api/v1/payment-sources', [
            'type' => 'wallet',
            'name' => 'Carteira',
        ]);

        $response->assertSuccessfulPrecognition();
        $this->assertDatabaseMissing('payment_sources', ['name' => 'Carteira']);
    }
}
