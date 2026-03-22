<?php

namespace App\Http\Requests\BrewMethod;

use Illuminate\Foundation\Http\FormRequest;

class StoreBrewMethodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100', 'unique:brew_methods,name'],
            'description' => ['nullable', 'string', 'max:500'],
        ];
    }
}
