<?php

namespace App\Http\Controllers;

use App\Http\Requests\Equipment\StoreEquipmentRequest;
use App\Http\Requests\Equipment\UpdateEquipmentRequest;
use App\Http\Resources\EquipmentResource;
use App\Models\Equipment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class EquipmentController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return EquipmentResource::collection(Equipment::global()->paginate(20));
    }

    public function show(Equipment $equipment): EquipmentResource
    {
        return new EquipmentResource($equipment);
    }

    public function store(StoreEquipmentRequest $request): EquipmentResource
    {
        $data = $request->validated();
        $isPersonal = $data['is_personal'] ?? false;
        unset($data['is_personal']);

        $data['user_id'] = $isPersonal ? $request->user()->id : null;

        $equipment = Equipment::create($data);

        return new EquipmentResource($equipment);
    }

    public function update(UpdateEquipmentRequest $request, Equipment $equipment): EquipmentResource
    {
        $this->authorize('update', $equipment);

        $equipment->update($request->validated());

        return new EquipmentResource($equipment);
    }

    /**
     * @response 204
     */
    public function destroy(Request $request, Equipment $equipment): JsonResponse
    {
        $this->authorize('delete', $equipment);

        $equipment->delete();

        return response()->json(null, 204);
    }

    public function myEquipment(Request $request): AnonymousResourceCollection
    {
        $equipment = Equipment::where('user_id', $request->user()->id)->paginate(20);

        return EquipmentResource::collection($equipment);
    }
}
