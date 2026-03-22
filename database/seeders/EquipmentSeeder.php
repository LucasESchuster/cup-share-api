<?php

namespace Database\Seeders;

use App\Enums\EquipmentType;
use App\Models\Equipment;
use Illuminate\Database\Seeder;

class EquipmentSeeder extends Seeder
{
    public function run(): void
    {
        $equipment = [
            // Grinders
            ['name' => 'Comandante C40', 'brand' => 'Comandante', 'model' => 'C40 MK4', 'type' => EquipmentType::Grinder],
            ['name' => 'Fellow Ode Gen 2', 'brand' => 'Fellow', 'model' => 'Ode Gen 2', 'type' => EquipmentType::Grinder],
            ['name' => 'Timemore C3', 'brand' => 'Timemore', 'model' => 'C3 Pro', 'type' => EquipmentType::Grinder],
            ['name' => 'Eureka Mignon Specialità', 'brand' => 'Eureka', 'model' => 'Mignon Specialità', 'type' => EquipmentType::Grinder],
            ['name' => 'Baratza Encore', 'brand' => 'Baratza', 'model' => 'Encore', 'type' => EquipmentType::Grinder],

            // Espresso machines
            ['name' => 'La Marzocco Linea Mini', 'brand' => 'La Marzocco', 'model' => 'Linea Mini', 'type' => EquipmentType::EspressoMachine],
            ['name' => 'Breville Barista Express', 'brand' => 'Breville', 'model' => 'Barista Express', 'type' => EquipmentType::EspressoMachine],
            ['name' => 'Gaggia Classic Pro', 'brand' => 'Gaggia', 'model' => 'Classic Pro', 'type' => EquipmentType::EspressoMachine],
            ['name' => 'Lelit Mara X', 'brand' => 'Lelit', 'model' => 'Mara X', 'type' => EquipmentType::EspressoMachine],

            // Scales
            ['name' => 'Acaia Pearl', 'brand' => 'Acaia', 'model' => 'Pearl', 'type' => EquipmentType::Scale],
            ['name' => 'Timemore Black Mirror', 'brand' => 'Timemore', 'model' => 'Black Mirror Basic+', 'type' => EquipmentType::Scale],

            // Drippers / pour-over
            ['name' => 'Hario V60 02', 'brand' => 'Hario', 'model' => 'V60 02', 'type' => EquipmentType::Dripper],
            ['name' => 'Kalita Wave 185', 'brand' => 'Kalita', 'model' => 'Wave 185', 'type' => EquipmentType::Dripper],
            ['name' => 'Chemex 6 xícaras', 'brand' => 'Chemex', 'model' => '6-Cup', 'type' => EquipmentType::Dripper],

            // Kettles
            ['name' => 'Fellow Stagg EKG', 'brand' => 'Fellow', 'model' => 'Stagg EKG', 'type' => EquipmentType::Kettle],
            ['name' => 'Hario V60 Buono', 'brand' => 'Hario', 'model' => 'V60 Buono', 'type' => EquipmentType::Kettle],
        ];

        foreach ($equipment as $item) {
            Equipment::firstOrCreate(
                ['name' => $item['name'], 'user_id' => null],
                array_merge($item, ['user_id' => null, 'type' => $item['type']->value])
            );
        }
    }
}
