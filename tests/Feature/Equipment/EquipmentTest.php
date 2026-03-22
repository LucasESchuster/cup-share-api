<?php

namespace Tests\Feature\Equipment;

use App\Enums\EquipmentType;
use App\Models\Equipment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EquipmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_global_equipment_is_visible_to_everyone(): void
    {
        Equipment::create(['name' => 'Comandante C40', 'type' => EquipmentType::Grinder->value, 'user_id' => null]);

        $this->getJson('/api/v1/equipment')
            ->assertStatus(200)
            ->assertJsonFragment(['name' => 'Comandante C40']);
    }

    public function test_user_can_create_personal_equipment(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/v1/equipment', [
                'name' => 'Meu moedor pessoal',
                'type' => 'grinder',
                'is_personal' => true,
            ])
            ->assertStatus(201)
            ->assertJsonFragment(['name' => 'Meu moedor pessoal', 'is_global' => false]);
    }

    public function test_personal_equipment_not_visible_in_global_list(): void
    {
        $user = User::factory()->create();
        Equipment::create(['name' => 'Moedor privado', 'type' => EquipmentType::Grinder->value, 'user_id' => $user->id]);

        $this->getJson('/api/v1/equipment')
            ->assertStatus(200)
            ->assertJsonMissing(['name' => 'Moedor privado']);
    }

    public function test_user_can_see_own_personal_equipment(): void
    {
        $user = User::factory()->create();
        Equipment::create(['name' => 'Meu moedor', 'type' => EquipmentType::Grinder->value, 'user_id' => $user->id]);

        $this->actingAs($user)
            ->getJson('/api/v1/users/me/equipment')
            ->assertStatus(200)
            ->assertJsonFragment(['name' => 'Meu moedor']);
    }

    public function test_user_cannot_delete_global_equipment(): void
    {
        $user = User::factory()->create();
        $global = Equipment::create(['name' => 'Global', 'type' => EquipmentType::Grinder->value, 'user_id' => null]);

        $this->actingAs($user)
            ->deleteJson("/api/v1/equipment/{$global->id}")
            ->assertStatus(403);
    }

    public function test_owner_can_delete_personal_equipment(): void
    {
        $user = User::factory()->create();
        $equipment = Equipment::create(['name' => 'Pessoal', 'type' => EquipmentType::Grinder->value, 'user_id' => $user->id]);

        $this->actingAs($user)
            ->deleteJson("/api/v1/equipment/{$equipment->id}")
            ->assertStatus(204);
    }
}
