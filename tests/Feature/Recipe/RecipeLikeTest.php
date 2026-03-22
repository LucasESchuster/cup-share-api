<?php

namespace Tests\Feature\Recipe;

use App\Models\BrewMethod;
use App\Models\Recipe;
use App\Models\RecipeType;
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

        $brewMethod = BrewMethod::create(['name' => 'V60', 'slug' => 'v60']);
        $recipeType = RecipeType::create(['name' => 'Filtrado', 'slug' => 'filtrado']);
        $this->owner = User::factory()->create();

        $this->recipe = Recipe::factory()->create([
            'user_id' => $this->owner->id,
            'brew_method_id' => $brewMethod->id,
            'recipe_type_id' => $recipeType->id,
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
}
