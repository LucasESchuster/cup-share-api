<?php

namespace Tests\Feature\Equipment;

use App\Enums\EquipmentType;
use App\Models\Equipment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EquipmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_equipment_is_visible_to_everyone(): void
    {
        Equipment::create(['name' => 'Comandante C40', 'type' => EquipmentType::Grinder->value]);

        $this->getJson('/api/v1/equipment')
            ->assertStatus(200)
            ->assertJsonFragment(['name' => 'Comandante C40']);
    }
}
