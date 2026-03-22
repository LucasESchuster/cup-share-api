<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RecipeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $reference = $this->water_ml ?? $this->yield_ml;
        $ratio = ($reference && $this->coffee_grams > 0)
            ? round($reference / $this->coffee_grams, 1)
            : null;

        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'description' => $this->description,
            'video_url' => $this->video_url,
            'visibility' => $this->visibility,
            'coffee_grams' => $this->coffee_grams,
            'water_ml' => $this->water_ml,
            'yield_ml' => $this->yield_ml,
            'ratio' => $ratio ? "1:{$ratio}" : null,
            'brew_time_seconds'          => $this->brew_time_seconds,
            'water_temperature_celsius'  => $this->water_temperature_celsius,
            'coffee_description'         => $this->coffee_description,
            'likes_count' => $this->likes_count,
            'brew_method' => new BrewMethodResource($this->whenLoaded('brewMethod')),
            'recipe_type' => new RecipeTypeResource($this->whenLoaded('recipeType')),
            'user' => new UserResource($this->whenLoaded('user')),
            'steps' => RecipeStepResource::collection($this->whenLoaded('steps')),
            'ingredients' => IngredientResource::collection($this->whenLoaded('ingredients')),
            'equipment' => EquipmentResource::collection($this->whenLoaded('equipment')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
