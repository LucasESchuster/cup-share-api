<?php

namespace Tests\Feature\E2E;

use App\Models\BrewMethod;
use App\Models\Recipe;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LikesCounterFlowTest extends TestCase
{
    use RefreshDatabase;

    private BrewMethod $brewMethod;
    private User $author;

    protected function setUp(): void
    {
        parent::setUp();

        $this->brewMethod = BrewMethod::create(['name' => 'V60', 'slug' => 'v60', 'category' => 'filter']);
        $this->author = User::factory()->create();
    }

    private function createRecipe(): Recipe
    {
        return Recipe::factory()->create([
            'user_id' => $this->author->id,
            'brew_method_id' => $this->brewMethod->id,
            'visibility' => 'public',
        ]);
    }

    public function test_multiple_users_likes_increment_counter_via_api(): void
    {
        $recipe = $this->createRecipe();
        $users = User::factory(3)->create();

        foreach ($users as $user) {
            $this->actingAs($user, 'sanctum')
                ->postJson("/api/v1/recipes/{$recipe->id}/likes")
                ->assertStatus(201);
        }

        $this->getJson("/api/v1/recipes/{$recipe->id}")
            ->assertStatus(200)
            ->assertJsonFragment(['likes_count' => 3]);

        $this->getJson("/api/v1/recipes/{$recipe->id}/likes")
            ->assertStatus(200)
            ->assertJsonFragment(['likes_count' => 3, 'liked_by_me' => false]);
    }

    public function test_unlike_decrements_counter_via_api(): void
    {
        $recipe = $this->createRecipe();
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/recipes/{$recipe->id}/likes")
            ->assertStatus(201)
            ->assertJsonFragment(['likes_count' => 1]);

        $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/v1/recipes/{$recipe->id}/likes")
            ->assertStatus(204);

        $this->getJson("/api/v1/recipes/{$recipe->id}")
            ->assertStatus(200)
            ->assertJsonFragment(['likes_count' => 0]);
    }

    public function test_counter_is_consistent_after_like_unlike_like_sequence(): void
    {
        $recipe = $this->createRecipe();
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/recipes/{$recipe->id}/likes")
            ->assertStatus(201);

        $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/v1/recipes/{$recipe->id}/likes")
            ->assertStatus(204);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/recipes/{$recipe->id}/likes")
            ->assertStatus(201)
            ->assertJsonFragment(['likes_count' => 1]);

        $this->getJson("/api/v1/recipes/{$recipe->id}")
            ->assertStatus(200)
            ->assertJsonFragment(['likes_count' => 1]);
    }

    public function test_user_cannot_like_own_recipe(): void
    {
        $recipe = $this->createRecipe();

        $this->actingAs($this->author, 'sanctum')
            ->postJson("/api/v1/recipes/{$recipe->id}/likes")
            ->assertStatus(403)
            ->assertJsonFragment(['message' => 'You cannot like your own recipe.']);

        $this->getJson("/api/v1/recipes/{$recipe->id}")
            ->assertJsonFragment(['likes_count' => 0]);
    }

    public function test_user_cannot_like_same_recipe_twice(): void
    {
        $recipe = $this->createRecipe();
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/recipes/{$recipe->id}/likes")
            ->assertStatus(201);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/recipes/{$recipe->id}/likes")
            ->assertStatus(422)
            ->assertJsonFragment(['message' => 'You have already liked this recipe.']);

        $this->getJson("/api/v1/recipes/{$recipe->id}")
            ->assertJsonFragment(['likes_count' => 1]);
    }

    public function test_liked_by_me_reflects_auth_users_like_state(): void
    {
        $recipe = $this->createRecipe();
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $this->withToken($token)
            ->getJson("/api/v1/recipes/{$recipe->id}/likes")
            ->assertJsonFragment(['liked_by_me' => false]);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/recipes/{$recipe->id}/likes")
            ->assertStatus(201);

        $this->withToken($token)
            ->getJson("/api/v1/recipes/{$recipe->id}/likes")
            ->assertJsonFragment(['liked_by_me' => true, 'likes_count' => 1]);
    }

    public function test_likes_on_private_recipe_return_404(): void
    {
        $private = Recipe::factory()->private()->create([
            'user_id' => $this->author->id,
            'brew_method_id' => $this->brewMethod->id,
        ]);
        $user = User::factory()->create();

        $this->getJson("/api/v1/recipes/{$private->id}/likes")
            ->assertStatus(404);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/recipes/{$private->id}/likes")
            ->assertStatus(404);
    }
}
