<?php

namespace App\Http\Requests\Recipe;

use App\Enums\BrewMethodCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class RecipeFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title'          => ['sometimes', 'string', 'max:200'],
            'brew_method_id' => ['sometimes', 'integer', 'exists:brew_methods,id'],
            'category'       => ['sometimes', 'string', new Enum(BrewMethodCategory::class)],
            'user_id'        => ['sometimes', 'integer', 'exists:users,id'],
            'published_from' => ['sometimes', 'date_format:Y-m-d'],
            'published_to'   => ['sometimes', 'date_format:Y-m-d', 'after_or_equal:published_from'],
            'sort_by'        => ['sometimes', 'in:created_at,likes_count'],
            'sort_dir'       => ['sometimes', 'in:asc,desc'],
        ];
    }
}
