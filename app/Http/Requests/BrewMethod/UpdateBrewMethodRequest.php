<?php

namespace App\Http\Requests\BrewMethod;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBrewMethodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:100', Rule::unique('brew_methods', 'name')->ignore($this->route('brew_method'))],
            'description' => ['nullable', 'string', 'max:500'],
        ];
    }
}
