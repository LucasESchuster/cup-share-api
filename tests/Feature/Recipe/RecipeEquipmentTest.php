<?php

namespace Tests\Feature\Recipe;

use App\Models\BrewMethod;
use App\Models\Equipment;
use App\Models\Recipe;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecipeEquipmentTest extends TestCase
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

    private function basePayload(array $overrides = []): array
    {
        return array_merge([
            'title'             => 'Receita com equipamento',
            'brew_method_id'    => $this->brewMethod->id,
            'coffee_grams'      => 15,
            'water_ml'          => 250,
            'brew_time_seconds' => 210,
        ], $overrides);
    }

    public function test_store_saves_global_equipment(): void
    {
        $grinder = Equipment::create(['name' => 'Comandante C40', 'type' => 'grinder']);

        $this->actingAs($this->user)
            ->postJson('/api/v1/recipes', $this->basePayload([
                'equipment' => [
                    ['equipment_id' => $grinder->id, 'grinder_clicks' => 24],
                ],
            ]))
            ->assertStatus(201);

        $recipe = Recipe::first();

        $this->assertDatabaseHas('recipe_equipment', [
            'recipe_id'      => $recipe->id,
            'equipment_id'   => $grinder->id,
            'grinder_clicks' => 24,
        ]);
    }

    public function test_store_saves_custom_equipment(): void
    {
        $this->actingAs($this->user)
            ->postJson('/api/v1/recipes', $this->basePayload([
                'equipment' => [
                    ['custom_name' => 'Filtro de papel', 'grinder_clicks' => null],
                ],
            ]))
            ->assertStatus(201);

        $recipe = Recipe::first();

        $this->assertDatabaseHas('recipe_equipment', [
            'recipe_id'    => $recipe->id,
            'equipment_id' => null,
            'custom_name'  => 'Filtro de papel',
        ]);
    }

    public function test_store_with_mixed_equipment(): void
    {
        $grinder = Equipment::create(['name' => 'Comandante C40', 'type' => 'grinder']);

        $this->actingAs($this->user)
            ->postJson('/api/v1/recipes', $this->basePayload([
                'equipment' => [
                    ['equipment_id' => $grinder->id, 'grinder_clicks' => 20],
                    ['custom_name' => 'Filtro de papel'],
                ],
            ]))
            ->assertStatus(201);

        $recipe = Recipe::first();

        $this->assertDatabaseCount('recipe_equipment', 2);
        $this->assertDatabaseHas('recipe_equipment', ['recipe_id' => $recipe->id, 'equipment_id' => $grinder->id]);
        $this->assertDatabaseHas('recipe_equipment', ['recipe_id' => $recipe->id, 'custom_name' => 'Filtro de papel']);
    }

    public function test_store_without_equipment_key_saves_no_entries(): void
    {
        $this->actingAs($this->user)
            ->postJson('/api/v1/recipes', $this->basePayload())
            ->assertStatus(201);

        $this->assertDatabaseCount('recipe_equipment', 0);
    }

    public function test_update_replaces_equipment(): void
    {
        $grinder = Equipment::create(['name' => 'Comandante C40', 'type' => 'grinder']);
        $kettle  = Equipment::create(['name' => 'Fellow Stagg', 'type' => 'kettle']);

        $recipe = Recipe::factory()->create([
            'user_id'        => $this->user->id,
            'brew_method_id' => $this->brewMethod->id,
        ]);
        $recipe->equipmentEntries()->create(['equipment_id' => $grinder->id]);

        $this->actingAs($this->user)
            ->putJson("/api/v1/recipes/{$recipe->id}", [
                'equipment' => [
                    ['equipment_id' => $kettle->id],
                ],
            ])
            ->assertStatus(200);

        $this->assertDatabaseMissing('recipe_equipment', ['equipment_id' => $grinder->id]);
        $this->assertDatabaseHas('recipe_equipment', ['recipe_id' => $recipe->id, 'equipment_id' => $kettle->id]);
    }

    public function test_update_without_equipment_key_preserves_existing_equipment(): void
    {
        $grinder = Equipment::create(['name' => 'Comandante C40', 'type' => 'grinder']);

        $recipe = Recipe::factory()->create([
            'user_id'        => $this->user->id,
            'brew_method_id' => $this->brewMethod->id,
        ]);
        $recipe->equipmentEntries()->create(['equipment_id' => $grinder->id]);

        $this->actingAs($this->user)
            ->putJson("/api/v1/recipes/{$recipe->id}", ['title' => 'Novo título'])
            ->assertStatus(200);

        $this->assertDatabaseHas('recipe_equipment', ['recipe_id' => $recipe->id, 'equipment_id' => $grinder->id]);
    }

    public function test_update_with_empty_equipment_array_removes_all_equipment(): void
    {
        $grinder = Equipment::create(['name' => 'Comandante C40', 'type' => 'grinder']);

        $recipe = Recipe::factory()->create([
            'user_id'        => $this->user->id,
            'brew_method_id' => $this->brewMethod->id,
        ]);
        $recipe->equipmentEntries()->create(['equipment_id' => $grinder->id]);

        $this->actingAs($this->user)
            ->putJson("/api/v1/recipes/{$recipe->id}", ['equipment' => []])
            ->assertStatus(200);

        $this->assertDatabaseCount('recipe_equipment', 0);
    }

    public function test_store_rejects_nonexistent_equipment_id(): void
    {
        $this->actingAs($this->user)
            ->postJson('/api/v1/recipes', $this->basePayload([
                'equipment' => [
                    ['equipment_id' => 99999],
                ],
            ]))
            ->assertStatus(422);
    }

    public function test_equipment_is_returned_in_store_response(): void
    {
        $grinder = Equipment::create(['name' => 'Comandante C40', 'type' => 'grinder']);

        $this->actingAs($this->user)
            ->postJson('/api/v1/recipes', $this->basePayload([
                'equipment' => [
                    ['equipment_id' => $grinder->id, 'grinder_clicks' => 18],
                ],
            ]))
            ->assertStatus(201)
            ->assertJsonPath('data.equipment.0.grinder_clicks', 18)
            ->assertJsonPath('data.equipment.0.equipment.id', $grinder->id);
    }
}
