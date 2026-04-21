<?php

namespace Tests\Feature\E2E;

use App\Models\BrewMethod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecipeLifecycleFlowTest extends TestCase
{
    use RefreshDatabase;

    private BrewMethod $brewMethod;

    protected function setUp(): void
    {
        parent::setUp();

        $this->brewMethod = BrewMethod::create(['name' => 'V60', 'slug' => 'v60', 'category' => 'filter']);
    }

    public function test_private_recipe_becomes_public_receives_like_and_is_deleted(): void
    {
        $author = User::factory()->create();
        $viewer = User::factory()->create();

        $created = $this->actingAs($author, 'sanctum')
            ->postJson('/api/v1/recipes', [
                'title' => 'Receita privada',
                'brew_method_id' => $this->brewMethod->id,
                'coffee_grams' => 18,
                'water_ml' => 300,
                'brew_time_seconds' => 240,
                'visibility' => 'private',
                'steps' => [
                    ['order' => 1, 'description' => 'Moer o café'],
                    ['order' => 2, 'description' => 'Molhar o filtro'],
                ],
            ])
            ->assertStatus(201)
            ->assertJsonFragment(['visibility' => 'private']);

        $recipeId = $created->json('data.id');

        $this->actingAs($viewer, 'sanctum')
            ->getJson("/api/v1/recipes/{$recipeId}")
            ->assertStatus(404);

        $this->getJson("/api/v1/recipes/{$recipeId}")
            ->assertStatus(404);

        $this->actingAs($author, 'sanctum')
            ->patchJson("/api/v1/recipes/{$recipeId}/visibility", ['visibility' => 'public'])
            ->assertStatus(200)
            ->assertJsonFragment(['visibility' => 'public']);

        $this->actingAs($viewer, 'sanctum')
            ->postJson("/api/v1/recipes/{$recipeId}/likes")
            ->assertStatus(201)
            ->assertJsonFragment(['likes_count' => 1]);

        $this->actingAs($author, 'sanctum')
            ->getJson("/api/v1/recipes/{$recipeId}")
            ->assertStatus(200)
            ->assertJsonFragment(['likes_count' => 1]);

        $this->actingAs($author, 'sanctum')
            ->deleteJson("/api/v1/recipes/{$recipeId}")
            ->assertStatus(204);

        $this->actingAs($viewer, 'sanctum')
            ->getJson("/api/v1/recipes/{$recipeId}")
            ->assertStatus(404);

        $this->assertSoftDeleted('recipes', ['id' => $recipeId]);
    }

    public function test_filter_recipe_has_ratio_calculated_from_water_ml(): void
    {
        $author = User::factory()->create();

        $response = $this->actingAs($author, 'sanctum')
            ->postJson('/api/v1/recipes', [
                'title' => 'Ratio filtro',
                'brew_method_id' => $this->brewMethod->id,
                'coffee_grams' => 15,
                'water_ml' => 250,
                'brew_time_seconds' => 210,
            ])
            ->assertStatus(201);

        $recipeId = $response->json('data.id');

        $this->getJson("/api/v1/recipes/{$recipeId}")
            ->assertStatus(200)
            ->assertJsonFragment([
                'water_ml' => 250,
                'yield_ml' => null,
                'ratio' => '1:16.7',
            ]);
    }

    public function test_espresso_recipe_has_ratio_calculated_from_yield_ml(): void
    {
        $espressoMethod = BrewMethod::create(['name' => 'Espresso', 'slug' => 'espresso', 'category' => 'espresso']);
        $author = User::factory()->create();

        $response = $this->actingAs($author, 'sanctum')
            ->postJson('/api/v1/recipes', [
                'title' => 'Ratio espresso',
                'brew_method_id' => $espressoMethod->id,
                'coffee_grams' => 18,
                'yield_ml' => 36,
                'brew_time_seconds' => 27,
            ])
            ->assertStatus(201);

        $recipeId = $response->json('data.id');

        $this->getJson("/api/v1/recipes/{$recipeId}")
            ->assertStatus(200)
            ->assertJsonFragment([
                'water_ml' => null,
                'yield_ml' => 36,
                'ratio' => '1:2',
            ]);
    }

    public function test_recipe_update_preserves_steps_when_steps_not_provided(): void
    {
        $author = User::factory()->create();

        $created = $this->actingAs($author, 'sanctum')
            ->postJson('/api/v1/recipes', [
                'title' => 'Preservação de steps',
                'brew_method_id' => $this->brewMethod->id,
                'coffee_grams' => 15,
                'water_ml' => 250,
                'brew_time_seconds' => 210,
                'steps' => [
                    ['order' => 1, 'description' => 'Passo 1'],
                    ['order' => 2, 'description' => 'Passo 2'],
                ],
            ])
            ->assertStatus(201);

        $recipeId = $created->json('data.id');
        $this->assertDatabaseCount('recipe_steps', 2);

        $this->actingAs($author, 'sanctum')
            ->putJson("/api/v1/recipes/{$recipeId}", ['title' => 'Só título mudou'])
            ->assertStatus(200)
            ->assertJsonFragment(['title' => 'Só título mudou']);

        $this->assertDatabaseCount('recipe_steps', 2);
    }
}
