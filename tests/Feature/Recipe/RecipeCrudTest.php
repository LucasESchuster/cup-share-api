<?php

namespace Tests\Feature\Recipe;

use App\Models\BrewMethod;
use App\Models\Recipe;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecipeCrudTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private BrewMethod $brewMethod;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->brewMethod = BrewMethod::create(['name' => 'V60', 'slug' => 'v60', 'category' => 'filter']);
    }

    private function recipePayload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Minha receita V60',
            'brew_method_id' => $this->brewMethod->id,
            'coffee_grams' => 15,
            'water_ml' => 250,
            'brew_time_seconds' => 210,
            'steps' => [
                ['order' => 1, 'description' => 'Pré-aqueça o filtro', 'duration_seconds' => null],
                ['order' => 2, 'description' => 'Jogue 30ml de água', 'duration_seconds' => 30],
            ],
        ], $overrides);
    }

    public function test_authenticated_user_can_create_recipe(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/recipes', $this->recipePayload())
            ->assertStatus(201)
            ->assertJsonFragment(['title' => 'Minha receita V60']);

        $this->assertDatabaseHas('recipes', ['title' => 'Minha receita V60', 'user_id' => $this->user->id]);
        $this->assertDatabaseCount('recipe_steps', 2);
    }

    public function test_unauthenticated_user_cannot_create_recipe(): void
    {
        $this->postJson('/api/v1/recipes', $this->recipePayload())
            ->assertStatus(401);
    }

    public function test_public_recipes_are_visible_to_everyone(): void
    {
        $recipe = Recipe::factory()->create([
            'user_id' => $this->user->id,
            'brew_method_id' => $this->brewMethod->id,
            'visibility' => 'public',
        ]);

        $this->getJson('/api/v1/recipes')
            ->assertStatus(200)
            ->assertJsonFragment(['id' => $recipe->id]);
    }

    public function test_owner_can_update_recipe(): void
    {
        $recipe = Recipe::factory()->create([
            'user_id' => $this->user->id,
            'brew_method_id' => $this->brewMethod->id,
        ]);

        $this->actingAs($this->user)
            ->putJson("/api/v1/recipes/{$recipe->id}", ['title' => 'Título atualizado'])
            ->assertStatus(200)
            ->assertJsonFragment(['title' => 'Título atualizado']);
    }

    public function test_other_user_cannot_update_recipe(): void
    {
        $otherUser = User::factory()->create();
        $recipe = Recipe::factory()->create([
            'user_id' => $this->user->id,
            'brew_method_id' => $this->brewMethod->id,
        ]);

        $this->actingAs($otherUser)
            ->putJson("/api/v1/recipes/{$recipe->id}", ['title' => 'Tentativa de hack'])
            ->assertStatus(403);
    }

    public function test_owner_can_delete_recipe(): void
    {
        $recipe = Recipe::factory()->create([
            'user_id' => $this->user->id,
            'brew_method_id' => $this->brewMethod->id,
        ]);

        $this->actingAs($this->user)
            ->deleteJson("/api/v1/recipes/{$recipe->id}")
            ->assertStatus(204);

        $this->assertSoftDeleted('recipes', ['id' => $recipe->id]);
    }
}
