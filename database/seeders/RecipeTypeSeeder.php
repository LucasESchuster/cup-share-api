<?php

namespace Database\Seeders;

use App\Models\RecipeType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class RecipeTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            'Filtrado',
            'Espresso',
            'Cold Brew',
            'Nitro',
            'Latte',
            'Cappuccino',
            'Flat White',
            'Macchiato',
            'Americano',
        ];

        foreach ($types as $name) {
            RecipeType::firstOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name, 'slug' => Str::slug($name)]
            );
        }
    }
}
