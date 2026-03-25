<?php

namespace Tests\Feature\Admin;

use App\Enums\EquipmentType;
use App\Models\Equipment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminEquipmentTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    private function user(): User
    {
        return User::factory()->create();
    }

    public function test_admin_can_create_equipment(): void
    {
        $this->actingAs($this->admin(), 'sanctum')
            ->postJson('/api/v1/admin/equipment', [
                'name'  => 'Comandante C40',
                'brand' => 'Comandante',
                'type'  => EquipmentType::Grinder->value,
            ])
            ->assertStatus(201)
            ->assertJsonFragment(['name' => 'Comandante C40']);

        $this->assertDatabaseHas('equipment', ['name' => 'Comandante C40']);
    }

    public function test_admin_can_update_equipment(): void
    {
        $equipment = Equipment::create(['name' => 'Old Grinder', 'type' => EquipmentType::Grinder->value]);

        $this->actingAs($this->admin(), 'sanctum')
            ->putJson("/api/v1/admin/equipment/{$equipment->id}", ['name' => 'New Grinder'])
            ->assertStatus(200)
            ->assertJsonFragment(['name' => 'New Grinder']);
    }

    public function test_admin_can_delete_equipment(): void
    {
        $equipment = Equipment::create(['name' => 'To Delete', 'type' => EquipmentType::Scale->value]);

        $this->actingAs($this->admin(), 'sanctum')
            ->deleteJson("/api/v1/admin/equipment/{$equipment->id}")
            ->assertStatus(204);

        $this->assertSoftDeleted('equipment', ['id' => $equipment->id]);
    }

    public function test_regular_user_cannot_create_equipment(): void
    {
        $this->actingAs($this->user(), 'sanctum')
            ->postJson('/api/v1/admin/equipment', ['name' => 'Hack', 'type' => EquipmentType::Grinder->value])
            ->assertStatus(403);
    }

    public function test_regular_user_cannot_update_equipment(): void
    {
        $equipment = Equipment::create(['name' => 'Grinder', 'type' => EquipmentType::Grinder->value]);

        $this->actingAs($this->user(), 'sanctum')
            ->putJson("/api/v1/admin/equipment/{$equipment->id}", ['name' => 'Hack'])
            ->assertStatus(403);
    }

    public function test_regular_user_cannot_delete_equipment(): void
    {
        $equipment = Equipment::create(['name' => 'Grinder', 'type' => EquipmentType::Grinder->value]);

        $this->actingAs($this->user(), 'sanctum')
            ->deleteJson("/api/v1/admin/equipment/{$equipment->id}")
            ->assertStatus(403);
    }

    public function test_public_equipment_index_remains_accessible(): void
    {
        Equipment::create(['name' => 'Public Grinder', 'type' => EquipmentType::Grinder->value]);

        $this->getJson('/api/v1/equipment')
            ->assertStatus(200)
            ->assertJsonFragment(['name' => 'Public Grinder']);
    }

    public function test_store_requires_name_and_type(): void
    {
        $this->actingAs($this->admin(), 'sanctum')
            ->postJson('/api/v1/admin/equipment', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'type']);
    }

    public function test_store_rejects_invalid_type(): void
    {
        $this->actingAs($this->admin(), 'sanctum')
            ->postJson('/api/v1/admin/equipment', ['name' => 'Foo', 'type' => 'invalid_type'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['type']);
    }
}
