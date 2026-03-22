<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RecipeStepResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'order' => $this->order,
            'description' => $this->description,
            'duration_seconds' => $this->duration_seconds,
        ];
    }
}
