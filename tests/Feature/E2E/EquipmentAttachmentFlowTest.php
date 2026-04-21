<?php

namespace Tests\Feature\E2E;

use App\Models\BrewMethod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EquipmentAttachmentFlowTest extends TestCase
{
    use RefreshDatabase;

    private BrewMethod $brewMethod;

    protected function setUp(): void
    {
        parent::setUp();

        $this->brewMethod = BrewMethod::create(['name' => 'V60', 'slug' => 'v60', 'category' => 'filter']);
    }

    public function test_admin_creates_global_equipment_and_user_attaches_to_recipe(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create();

        $equipmentResponse = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/admin/equipment', [
                'name' => 'Hario V60',
                'brand' => 'Hario',
                'model' => '02',
                'type' => 'dripper',
            ])
            ->assertStatus(201)
            ->assertJsonFragment(['name' => 'Hario V60', 'type' => 'dripper']);

        $equipmentId = $equipmentResponse->json('data.id');
        $this->assertNotNull($equipmentId);

        $this->getJson('/api/v1/equipment')
            ->assertStatus(200)
            ->assertJsonFragment(['id' => $equipmentId, 'name' => 'Hario V60']);

        $recipeResponse = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/recipes', [
                'title' => 'Receita com equipamento',
                'brew_method_id' => $this->brewMethod->id,
                'coffee_grams' => 15,
                'water_ml' => 250,
                'brew_time_seconds' => 210,
            ])
            ->assertStatus(201);

        $recipeId = $recipeResponse->json('data.id');

        $attachResponse = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/recipes/{$recipeId}/equipment", [
                'equipment_id' => $equipmentId,
                'grinder_clicks' => 18,
                'parameters' => ['coarseness' => 'medium'],
            ])
            ->assertStatus(201)
            ->assertJsonFragment(['grinder_clicks' => 18])
            ->assertJsonPath('data.parameters.coarseness', 'medium');

        $recipeEquipmentId = $attachResponse->json('data.id');

        $this->getJson("/api/v1/recipes/{$recipeId}")
            ->assertStatus(200)
            ->assertJsonPath('data.equipment.0.equipment.id', $equipmentId)
            ->assertJsonPath('data.equipment.0.grinder_clicks', 18);

        $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/v1/recipes/{$recipeId}/equipment/{$recipeEquipmentId}")
            ->assertStatus(204);

        $this->getJson("/api/v1/recipes/{$recipeId}")
            ->assertStatus(200)
            ->assertJsonPath('data.equipment', []);
    }

    public function test_user_can_attach_equipment_via_custom_name_without_equipment_id(): void
    {
        $user = User::factory()->create();

        $recipeResponse = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/recipes', [
                'title' => 'Com custom_name',
                'brew_method_id' => $this->brewMethod->id,
                'coffee_grams' => 15,
                'water_ml' => 250,
                'brew_time_seconds' => 210,
            ])
            ->assertStatus(201);

        $recipeId = $recipeResponse->json('data.id');

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/recipes/{$recipeId}/equipment", [
                'custom_name' => 'Moedor caseiro sem marca',
                'grinder_clicks' => 22,
            ])
            ->assertStatus(201)
            ->assertJsonFragment(['custom_name' => 'Moedor caseiro sem marca', 'grinder_clicks' => 22]);
    }

    public function test_attaching_equipment_requires_either_equipment_id_or_custom_name(): void
    {
        $user = User::factory()->create();

        $recipeResponse = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/recipes', [
                'title' => 'Sem eq',
                'brew_method_id' => $this->brewMethod->id,
                'coffee_grams' => 15,
                'water_ml' => 250,
                'brew_time_seconds' => 210,
            ])
            ->assertStatus(201);

        $recipeId = $recipeResponse->json('data.id');

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/recipes/{$recipeId}/equipment", ['grinder_clicks' => 20])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['equipment_id', 'custom_name']);
    }

    public function test_non_admin_cannot_create_global_equipment(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/admin/equipment', [
                'name' => 'Equipamento qualquer',
                'type' => 'other',
            ])
            ->assertStatus(403);
    }

    public function test_non_owner_cannot_attach_equipment_to_others_recipe(): void
    {
        $owner = User::factory()->create();
        $attacker = User::factory()->create();

        $recipeResponse = $this->actingAs($owner, 'sanctum')
            ->postJson('/api/v1/recipes', [
                'title' => 'Alheia',
                'brew_method_id' => $this->brewMethod->id,
                'coffee_grams' => 15,
                'water_ml' => 250,
                'brew_time_seconds' => 210,
            ])
            ->assertStatus(201);

        $recipeId = $recipeResponse->json('data.id');

        $this->actingAs($attacker, 'sanctum')
            ->postJson("/api/v1/recipes/{$recipeId}/equipment", ['custom_name' => 'Hack'])
            ->assertStatus(403);
    }
}
