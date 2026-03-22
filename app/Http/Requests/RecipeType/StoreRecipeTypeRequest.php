<?php

namespace App\Http\Requests\RecipeType;

use Illuminate\Foundation\Http\FormRequest;

class StoreRecipeTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100', 'unique:recipe_types,name'],
        ];
    }
}
