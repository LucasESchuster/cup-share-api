<?php

namespace Database\Seeders;

use App\Models\Ingredient;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class IngredientSeeder extends Seeder
{
    public function run(): void
    {
        $ingredients = [
            'Leite integral',
            'Leite desnatado',
            'Leite vegetal de aveia',
            'Leite vegetal de amêndoa',
            'Leite vegetal de coco',
            'Açúcar',
            'Açúcar mascavo',
            'Mel',
            'Gelo',
            'Creme de leite',
            'Chocolate em pó',
            'Canela em pó',
            'Baunilha',
        ];

        foreach ($ingredients as $name) {
            Ingredient::firstOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name, 'slug' => Str::slug($name)]
            );
        }
    }
}
