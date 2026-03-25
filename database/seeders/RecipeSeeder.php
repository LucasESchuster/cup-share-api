<?php

namespace Database\Seeders;

use App\Models\BrewMethod;
use App\Models\Equipment;
use App\Models\Recipe;
use App\Models\User;
use Illuminate\Database\Seeder;

class RecipeSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::factory(5)->create();
        $brewMethods = BrewMethod::all();
        $equipment = Equipment::all();

        // 80 receitas de filtro (water_ml)
        Recipe::factory(80)
            ->recycle($users)
            ->recycle($brewMethods)
            ->create();

        // 20 receitas de espresso (yield_ml)
        Recipe::factory(20)
            ->espresso()
            ->recycle($users)
            ->recycle($brewMethods)
            ->create();

        // Tornar ~20% das receitas privadas
        Recipe::inRandomOrder()
            ->limit(20)
            ->get()
            ->each->update(['visibility' => 'private']);

        // Associar 1-3 equipamentos aleatórios a ~60 receitas
        if ($equipment->isNotEmpty()) {
            Recipe::inRandomOrder()
                ->limit(60)
                ->get()
                ->each(function (Recipe $recipe) use ($equipment) {
                    $recipe->equipment()->attach(
                        $equipment->random(min(rand(1, 3), $equipment->count()))->pluck('id')
                    );
                });
        }
    }
}
