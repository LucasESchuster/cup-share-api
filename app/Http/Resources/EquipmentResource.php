<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EquipmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'brand' => $this->brand,
            'model' => $this->model,
            'type' => $this->type,
            'pivot' => $this->when($this->pivot !== null, fn () => [
                'grinder_clicks' => $this->pivot->grinder_clicks,
                'parameters' => $this->pivot->parameters ? json_decode($this->pivot->parameters, true) : null,
            ]),
        ];
    }
}
