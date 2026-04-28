<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use Database\Seeders\DefaultCategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CategoryUserPreferencesApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_hides_a_category_for_the_authenticated_user(): void
    {
        $this->seed(DefaultCategorySeeder::class);
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $category = Category::query()->where('slug', 'assinaturas')->firstOrFail();

        $response = $this->putJson("/api/v1/categories/{$category->id}/preferences", [
            'is_hidden' => true,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.is_hidden', true);

        $hiddenResponse = $this->getJson('/api/v1/categories');
        $hiddenResponse->assertOk()
            ->assertJsonMissing(['id' => $category->id]);

        $withHiddenResponse = $this->getJson('/api/v1/categories?filter[with_hidden]=1');
        $withHiddenResponse->assertOk()
            ->assertJsonFragment(['id' => $category->id, 'is_hidden' => true]);
    }

    public function test_it_persists_display_order_override_for_a_user(): void
    {
        $this->seed(DefaultCategorySeeder::class);
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $category = Category::query()->where('slug', 'alimentacao')->firstOrFail();

        $response = $this->putJson("/api/v1/categories/{$category->id}/preferences", [
            'display_order_override' => 1,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.display_order_override', 1);

        $this->assertDatabaseHas('category_user_preferences', [
            'user_id' => $user->id,
            'category_id' => $category->id,
            'display_order_override' => 1,
        ]);
    }

    public function test_it_rejects_preference_for_foreign_custom_category(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        Sanctum::actingAs($user);

        $category = Category::factory()->expense()->create(['user_id' => $otherUser->id]);

        $response = $this->putJson("/api/v1/categories/{$category->id}/preferences", [
            'is_hidden' => true,
        ]);

        $response->assertNotFound();
    }

    public function test_preference_rejects_unknown_fields_and_invalid_order(): void
    {
        $this->seed(DefaultCategorySeeder::class);
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $category = Category::query()->where('slug', 'alimentacao')->firstOrFail();

        $response = $this->putJson("/api/v1/categories/{$category->id}/preferences", [
            'display_order_override' => -1,
            'unexpected' => 'blocked',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['display_order_override', 'unexpected']);
    }
}
