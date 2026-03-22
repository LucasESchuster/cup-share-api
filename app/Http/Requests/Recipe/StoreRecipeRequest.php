<?php

namespace App\Http\Requests\Recipe;

use App\Enums\RecipeVisibility;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class StoreRecipeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:2000'],
            'brew_method_id' => ['required', 'integer', 'exists:brew_methods,id'],
            'recipe_type_id' => ['required', 'integer', 'exists:recipe_types,id'],
            'coffee_grams' => ['required', 'numeric', 'min:0.1', 'max:9999'],
            'water_ml' => ['nullable', 'integer', 'min:1', 'required_without:yield_ml'],
            'yield_ml' => ['nullable', 'integer', 'min:1', 'required_without:water_ml'],
            'brew_time_seconds' => ['required', 'integer', 'min:1'],
            'visibility' => ['sometimes', new Enum(RecipeVisibility::class)],
            'video_url'                  => ['nullable', 'url', 'max:2048'],
            'water_temperature_celsius'  => ['nullable', 'integer', 'min:0', 'max:100'],
            'coffee_description'         => ['nullable', 'string', 'max:1000'],

            // Steps
            'steps' => ['sometimes', 'array'],
            'steps.*.order' => ['required_with:steps', 'integer', 'min:1'],
            'steps.*.description' => ['required_with:steps', 'string', 'max:1000'],
            'steps.*.duration_seconds' => ['nullable', 'integer', 'min:1'],

            // Extra ingredients
            'ingredients' => ['sometimes', 'array'],
            'ingredients.*.id' => ['required_with:ingredients', 'integer', 'exists:ingredients,id'],
            'ingredients.*.quantity' => ['required_with:ingredients', 'numeric', 'min:0.01'],
            'ingredients.*.unit' => ['required_with:ingredients', 'string', 'max:50'],
        ];
    }
}
