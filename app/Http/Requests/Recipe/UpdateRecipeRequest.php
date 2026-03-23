<?php

namespace App\Http\Requests\Recipe;

use App\Enums\RecipeVisibility;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateRecipeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:2000'],
            'brew_method_id' => ['sometimes', 'integer', 'exists:brew_methods,id'],
            'coffee_grams' => ['sometimes', 'numeric', 'min:0.1', 'max:9999'],
            'water_ml' => ['nullable', 'integer', 'min:1'],
            'yield_ml' => ['nullable', 'integer', 'min:1'],
            'brew_time_seconds' => ['sometimes', 'integer', 'min:1'],
            'visibility' => ['sometimes', new Enum(RecipeVisibility::class)],
            'video_url'                  => ['sometimes', 'nullable', 'url', 'max:2048'],
            'water_temperature_celsius'  => ['sometimes', 'nullable', 'integer', 'min:0', 'max:100'],
            'coffee_description'         => ['sometimes', 'nullable', 'string', 'max:1000'],

            // Steps (full replace when provided)
            'steps' => ['sometimes', 'array'],
            'steps.*.order' => ['required_with:steps', 'integer', 'min:1'],
            'steps.*.description' => ['required_with:steps', 'string', 'max:1000'],
        ];
    }
}
