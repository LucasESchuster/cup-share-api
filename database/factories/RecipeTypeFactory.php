<?php

namespace Database\Factories;

use App\Models\RecipeType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<RecipeType>
 */
class RecipeTypeFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->word().' Type';

        return [
            'name' => $name,
            'slug' => Str::slug($name),
        ];
    }
}
