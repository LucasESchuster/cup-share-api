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
            ['name' => 'V60',           'description' => 'Método de coagem por gotejamento com filtro cônico.',          'category' => 'filter'],
            ['name' => 'Aeropress',     'description' => 'Método de pressão manual com câmara de ar.',                   'category' => 'filter'],
            ['name' => 'Chemex',        'description' => 'Coagem por gotejamento com filtro espesso e jarra elegante.',   'category' => 'filter'],
            ['name' => 'Moka Pot',      'description' => 'Cafeteira italiana de pressão a vapor no fogão.',               'category' => 'pressure'],
            ['name' => 'French Press',  'description' => 'Imersão total com êmbolo para separar a borra.',                'category' => 'filter'],
            ['name' => 'Kalita Wave',   'description' => 'Coagem plana com três furos para extração uniforme.',           'category' => 'filter'],
            ['name' => 'Clever Dripper', 'description' => 'Combinação de imersão e coagem por gotejamento.',             'category' => 'filter'],
            ['name' => 'Espresso',      'description' => 'Extração sob alta pressão (9 bar) com água quente.',            'category' => 'espresso'],
            ['name' => 'Cold Brew',     'description' => 'Imersão em água fria por 12 a 24 horas.',                      'category' => 'cold_brew'],
        ];

        foreach ($methods as $method) {
            BrewMethod::firstOrCreate(
                ['slug' => Str::slug($method['name'])],
                array_merge($method, ['slug' => Str::slug($method['name'])])
            );
        }
    }
}
