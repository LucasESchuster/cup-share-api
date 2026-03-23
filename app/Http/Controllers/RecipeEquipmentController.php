<?php

namespace App\Http\Controllers;

use App\Http\Requests\Recipe\RecipeEquipmentRequest;
use App\Http\Resources\RecipeEquipmentResource;
use App\Models\Recipe;
use App\Models\RecipeEquipment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RecipeEquipmentController extends Controller
{
    public function store(RecipeEquipmentRequest $request, Recipe $recipe): RecipeEquipmentResource
    {
        $this->authorize('update', $recipe);

        $data = $request->validated();

        $entry = $recipe->equipmentEntries()->create([
            'equipment_id'   => $data['equipment_id'] ?? null,
            'custom_name'    => $data['custom_name'] ?? null,
            'grinder_clicks' => $data['grinder_clicks'] ?? null,
            'parameters'     => isset($data['parameters']) ? json_encode($data['parameters']) : null,
        ]);

        $entry->load('equipment');

        return new RecipeEquipmentResource($entry);
    }

    public function update(Request $request, Recipe $recipe, RecipeEquipment $recipeEquipment): RecipeEquipmentResource
    {
        $this->authorize('update', $recipe);

        $request->validate([
            'grinder_clicks' => ['nullable', 'integer', 'min:0'],
            'parameters'     => ['nullable', 'array'],
        ]);

        $recipeEquipment->update([
            'grinder_clicks' => $request->grinder_clicks,
            'parameters'     => $request->parameters ? json_encode($request->parameters) : null,
        ]);

        $recipeEquipment->load('equipment');

        return new RecipeEquipmentResource($recipeEquipment);
    }

    /**
     * @response 204
     */
    public function destroy(Recipe $recipe, RecipeEquipment $recipeEquipment): JsonResponse
    {
        $this->authorize('update', $recipe);

        $recipeEquipment->delete();

        return response()->json(null, 204);
    }
}
