<?php

namespace App\Http\Requests\Recipe;

use Illuminate\Foundation\Http\FormRequest;

class RecipeEquipmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'equipment_id'   => ['nullable', 'integer', 'exists:equipment,id', 'required_without:custom_name'],
            'custom_name'    => ['nullable', 'string', 'max:150', 'required_without:equipment_id'],
            'grinder_clicks' => ['nullable', 'integer', 'min:0'],
            'parameters'     => ['nullable', 'array'],
        ];
    }
}
