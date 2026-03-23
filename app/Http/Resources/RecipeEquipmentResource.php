<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RecipeEquipmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'custom_name'    => $this->custom_name,
            'grinder_clicks' => $this->grinder_clicks,
            'parameters'     => $this->parameters ? json_decode($this->parameters, true) : null,
            'equipment'      => $this->whenLoaded('equipment', fn () => $this->equipment
                ? new EquipmentResource($this->equipment)
                : null
            ),
        ];
    }
}
