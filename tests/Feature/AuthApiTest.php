<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_registers_and_returns_a_token(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'device_name' => 'tests',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.user.email', 'jane@example.com');

        $this->assertDatabaseHas('users', ['email' => 'jane@example.com']);
    }

    public function test_register_rejects_invalid_and_unknown_fields(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Jane Doe',
            'email' => 'not-an-email',
            'password' => 'short',
            'password_confirmation' => 'different',
            'currency_code' => 'BRL',
            'unexpected' => 'blocked',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email', 'password', 'unexpected']);
    }

    public function test_register_precognition_does_not_create_user(): void
    {
        $response = $this->withPrecognition()->postJson('/api/v1/auth/register', [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'device_name' => 'tests',
        ]);

        $response->assertSuccessfulPrecognition();
        $this->assertDatabaseMissing('users', ['email' => 'jane@example.com']);
    }

    public function test_login_rejects_invalid_credentials(): void
    {
        User::factory()->create([
            'email' => 'jane@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'jane@example.com',
            'password' => 'wrong-password',
            'device_name' => 'tests',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('message', 'Invalid credentials.');
    }

    public function test_it_returns_the_authenticated_user(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/auth/me');

        $response->assertOk()
            ->assertJsonPath('data.email', $user->email);
    }
}
