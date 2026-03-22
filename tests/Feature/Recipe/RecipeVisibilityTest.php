<?php

namespace Tests\Feature\Recipe;

use App\Models\BrewMethod;
use App\Models\Recipe;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecipeVisibilityTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;
    private BrewMethod $brewMethod;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create();
        $this->brewMethod = BrewMethod::create(['name' => 'V60', 'slug' => 'v60', 'category' => 'filter']);
    }

    public function test_private_recipe_is_not_in_public_feed(): void
    {
        Recipe::factory()->create([
            'user_id' => $this->owner->id,
            'brew_method_id' => $this->brewMethod->id,
            'visibility' => 'private',
            'title' => 'Segredo',
        ]);

        $this->getJson('/api/v1/recipes')
            ->assertStatus(200)
            ->assertJsonMissing(['title' => 'Segredo']);
    }

    public function test_private_recipe_returns_404_for_other_user(): void
    {
        $otherUser = User::factory()->create();
        $recipe = Recipe::factory()->create([
            'user_id' => $this->owner->id,
            'brew_method_id' => $this->brewMethod->id,
            'visibility' => 'private',
        ]);

        $this->actingAs($otherUser)
            ->getJson("/api/v1/recipes/{$recipe->id}")
            ->assertStatus(404);
    }

    public function test_owner_can_see_own_private_recipe(): void
    {
        $recipe = Recipe::factory()->create([
            'user_id' => $this->owner->id,
            'brew_method_id' => $this->brewMethod->id,
            'visibility' => 'private',
            'title' => 'Minha privada',
        ]);

        $this->actingAs($this->owner)
            ->getJson("/api/v1/recipes/{$recipe->id}")
            ->assertStatus(200)
            ->assertJsonFragment(['title' => 'Minha privada']);
    }

    public function test_owner_can_toggle_visibility(): void
    {
        $recipe = Recipe::factory()->create([
            'user_id' => $this->owner->id,
            'brew_method_id' => $this->brewMethod->id,
            'visibility' => 'public',
        ]);

        $this->actingAs($this->owner)
            ->patchJson("/api/v1/recipes/{$recipe->id}/visibility", ['visibility' => 'private'])
            ->assertStatus(200);

        $this->assertDatabaseHas('recipes', ['id' => $recipe->id, 'visibility' => 'private']);
    }
}
