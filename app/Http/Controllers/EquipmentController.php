<?php

namespace App\Http\Controllers;

use App\Http\Requests\Equipment\StoreEquipmentRequest;
use App\Http\Requests\Equipment\UpdateEquipmentRequest;
use App\Http\Resources\EquipmentResource;
use App\Models\Equipment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class EquipmentController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return EquipmentResource::collection(Equipment::paginate(20));
    }

    public function show(Equipment $equipment): EquipmentResource
    {
        return new EquipmentResource($equipment);
    }

    public function store(StoreEquipmentRequest $request): JsonResponse
    {
        $equipment = Equipment::create($request->validated());

        return (new EquipmentResource($equipment))
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateEquipmentRequest $request, Equipment $equipment): EquipmentResource
    {
        $equipment->update($request->validated());

        return new EquipmentResource($equipment);
    }

    public function destroy(Equipment $equipment): JsonResponse
    {
        $equipment->delete();

        return response()->json(null, 204);
    }
}
