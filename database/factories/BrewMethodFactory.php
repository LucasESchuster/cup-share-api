<?php

namespace Database\Factories;

use App\Models\BrewMethod;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<BrewMethod>
 */
class BrewMethodFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->word().' Method';

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => fake()->optional()->sentence(),
        ];
    }
}
