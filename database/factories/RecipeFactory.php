<?php

namespace Database\Factories;

use App\Enums\RecipeVisibility;
use App\Models\BrewMethod;
use App\Models\Recipe;
use App\Models\RecipeType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Recipe>
 */
class RecipeFactory extends Factory
{
    public function definition(): array
    {
        $title = fake()->sentence(3);

        return [
            'user_id' => User::factory(),
            'brew_method_id' => BrewMethod::factory(),
            'recipe_type_id' => RecipeType::factory(),
            'title' => $title,
            'slug' => Str::slug($title).'-'.Str::random(6),
            'description' => fake()->optional()->paragraph(),
            'coffee_grams' => fake()->randomFloat(1, 10, 30),
            'water_ml' => fake()->numberBetween(150, 500),
            'yield_ml' => null,
            'brew_time_seconds' => fake()->numberBetween(60, 600),
            'visibility' => RecipeVisibility::Public->value,
        ];
    }

    public function private(): static
    {
        return $this->state(['visibility' => RecipeVisibility::Private->value]);
    }

    public function espresso(): static
    {
        return $this->state([
            'water_ml' => null,
            'yield_ml' => fake()->numberBetween(25, 50),
        ]);
    }
}
