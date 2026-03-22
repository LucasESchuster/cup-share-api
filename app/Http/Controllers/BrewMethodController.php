<?php

namespace App\Http\Controllers;

use App\Http\Requests\BrewMethod\StoreBrewMethodRequest;
use App\Http\Requests\BrewMethod\UpdateBrewMethodRequest;
use App\Http\Resources\BrewMethodResource;
use App\Models\BrewMethod;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;

class BrewMethodController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return BrewMethodResource::collection(BrewMethod::all());
    }

    public function show(BrewMethod $brewMethod): BrewMethodResource
    {
        return new BrewMethodResource($brewMethod);
    }

    public function store(StoreBrewMethodRequest $request): BrewMethodResource
    {
        $data = $request->validated();
        $data['slug'] = Str::slug($data['name']);

        $brewMethod = BrewMethod::create($data);

        return new BrewMethodResource($brewMethod);
    }

    public function update(UpdateBrewMethodRequest $request, BrewMethod $brewMethod): BrewMethodResource
    {
        $data = $request->validated();

        if (isset($data['name'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        $brewMethod->update($data);

        return new BrewMethodResource($brewMethod);
    }

    /**
     * @response 204
     */
    public function destroy(BrewMethod $brewMethod): JsonResponse
    {
        $brewMethod->delete();

        return response()->json(null, 204);
    }
}
