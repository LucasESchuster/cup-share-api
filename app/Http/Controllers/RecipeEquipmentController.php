<?php

namespace App\Http\Controllers;

use App\Http\Requests\Recipe\RecipeEquipmentRequest;
use App\Http\Resources\EquipmentResource;
use App\Models\Equipment;
use App\Models\Recipe;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RecipeEquipmentController extends Controller
{
    public function store(RecipeEquipmentRequest $request, Recipe $recipe): EquipmentResource
    {
        $this->authorize('update', $recipe);

        $data = $request->validated();
        $equipment = Equipment::findOrFail($data['equipment_id']);

        $recipe->equipment()->syncWithoutDetaching([
            $equipment->id => [
                'grinder_clicks' => $data['grinder_clicks'] ?? null,
                'parameters' => isset($data['parameters']) ? json_encode($data['parameters']) : null,
            ],
        ]);

        $equipment->pivot = $recipe->equipment()->where('equipment_id', $equipment->id)->first()->pivot;

        return new EquipmentResource($equipment);
    }

    public function update(Request $request, Recipe $recipe, Equipment $equipment): EquipmentResource
    {
        $this->authorize('update', $recipe);

        $request->validate([
            'grinder_clicks' => ['nullable', 'integer', 'min:0'],
            'parameters' => ['nullable', 'array'],
        ]);

        $recipe->equipment()->updateExistingPivot($equipment->id, [
            'grinder_clicks' => $request->grinder_clicks,
            'parameters' => $request->parameters ? json_encode($request->parameters) : null,
        ]);

        $equipment->pivot = $recipe->equipment()->where('equipment_id', $equipment->id)->first()->pivot;

        return new EquipmentResource($equipment);
    }

    /**
     * @response 204
     */
    public function destroy(Recipe $recipe, Equipment $equipment): JsonResponse
    {
        $this->authorize('update', $recipe);

        $recipe->equipment()->detach($equipment->id);

        return response()->json(null, 204);
    }
}
