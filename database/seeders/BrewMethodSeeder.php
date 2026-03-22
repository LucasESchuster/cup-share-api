<?php

namespace Database\Seeders;

use App\Models\BrewMethod;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class BrewMethodSeeder extends Seeder
{
    public function run(): void
    {
        $methods = [
            ['name' => 'V60', 'description' => 'Método de coagem por gotejamento com filtro cônico.'],
            ['name' => 'Aeropress', 'description' => 'Método de pressão manual com câmara de ar.'],
            ['name' => 'Chemex', 'description' => 'Coagem por gotejamento com filtro espesso e jarra elegante.'],
            ['name' => 'Moka Pot', 'description' => 'Cafeteira italiana de pressão a vapor no fogão.'],
            ['name' => 'French Press', 'description' => 'Imersão total com êmbolo para separar a borra.'],
            ['name' => 'Kalita Wave', 'description' => 'Coagem plana com três furos para extração uniforme.'],
            ['name' => 'Clever Dripper', 'description' => 'Combinação de imersão e coagem por gotejamento.'],
            ['name' => 'Espresso', 'description' => 'Extração sob alta pressão (9 bar) com água quente.'],
            ['name' => 'Cold Brew', 'description' => 'Imersão em água fria por 12 a 24 horas.'],
        ];

        foreach ($methods as $method) {
            BrewMethod::firstOrCreate(
                ['slug' => Str::slug($method['name'])],
                array_merge($method, ['slug' => Str::slug($method['name'])])
            );
        }
    }
}
