<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class IngredientResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'pivot' => $this->when($this->pivot !== null, fn () => [
                'quantity' => $this->pivot->quantity,
                'unit' => $this->pivot->unit,
            ]),
        ];
    }
}
