<?php

namespace Tests\Feature\Recipe;

use App\Models\BrewMethod;
use App\Models\Recipe;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecipeLikeTest extends TestCase
{
    use RefreshDatabase;

    private Recipe $recipe;
    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        $brewMethod = BrewMethod::create(['name' => 'V60', 'slug' => 'v60', 'category' => 'filter']);
        $this->owner = User::factory()->create();

        $this->recipe = Recipe::factory()->create([
            'user_id' => $this->owner->id,
            'brew_method_id' => $brewMethod->id,
            'visibility' => 'public',
        ]);
    }

    public function test_user_can_like_public_recipe(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson("/api/v1/recipes/{$this->recipe->id}/likes")
            ->assertStatus(201);

        $this->assertDatabaseHas('likes', ['user_id' => $user->id, 'recipe_id' => $this->recipe->id]);
    }

    public function test_user_cannot_like_own_recipe(): void
    {
        $this->actingAs($this->owner)
            ->postJson("/api/v1/recipes/{$this->recipe->id}/likes")
            ->assertStatus(403);
    }

    public function test_user_cannot_like_same_recipe_twice(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson("/api/v1/recipes/{$this->recipe->id}/likes")->assertStatus(201);
        $this->actingAs($user)->postJson("/api/v1/recipes/{$this->recipe->id}/likes")->assertStatus(422);
    }

    public function test_user_can_remove_like(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson("/api/v1/recipes/{$this->recipe->id}/likes");

        $this->actingAs($user)
            ->deleteJson("/api/v1/recipes/{$this->recipe->id}/likes")
            ->assertStatus(204);

        $this->assertDatabaseMissing('likes', ['user_id' => $user->id, 'recipe_id' => $this->recipe->id]);
    }

    public function test_anyone_can_see_likes_count(): void
    {
        $this->getJson("/api/v1/recipes/{$this->recipe->id}/likes")
            ->assertStatus(200)
            ->assertJsonStructure(['likes_count', 'liked_by_me']);
    }

    public function test_liked_by_me_is_true_for_authenticated_user_who_liked(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/recipes/{$this->recipe->id}/likes")
            ->assertStatus(201)
            ->assertJson(['likes_count' => 1]);

        $this->withHeaders(['Authorization' => "Bearer $token"])
            ->getJson("/api/v1/recipes/{$this->recipe->id}/likes")
            ->assertJson(['likes_count' => 1, 'liked_by_me' => true]);
    }

    public function test_liked_by_me_is_false_for_anonymous_user(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/recipes/{$this->recipe->id}/likes");

        $this->getJson("/api/v1/recipes/{$this->recipe->id}/likes")
            ->assertJson(['likes_count' => 1, 'liked_by_me' => false]);
    }

    public function test_recipe_index_includes_liked_by_me_true_for_authenticated_user(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/recipes/{$this->recipe->id}/likes");

        $this->withHeaders(['Authorization' => "Bearer $token"])
            ->getJson('/api/v1/recipes')
            ->assertJsonFragment(['id' => $this->recipe->id, 'liked_by_me' => true]);
    }

    public function test_recipe_index_includes_liked_by_me_false_for_anonymous_user(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/recipes/{$this->recipe->id}/likes");

        $this->getJson('/api/v1/recipes')
            ->assertJsonFragment(['id' => $this->recipe->id, 'liked_by_me' => false]);
    }

    public function test_recipe_show_includes_liked_by_me_true_for_authenticated_user(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/recipes/{$this->recipe->id}/likes");

        $this->withHeaders(['Authorization' => "Bearer $token"])
            ->getJson("/api/v1/recipes/{$this->recipe->id}")
            ->assertJsonFragment(['liked_by_me' => true]);
    }
}
