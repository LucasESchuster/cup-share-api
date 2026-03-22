<?php

namespace App\Http\Controllers;

use App\Http\Requests\RecipeType\StoreRecipeTypeRequest;
use App\Http\Requests\RecipeType\UpdateRecipeTypeRequest;
use App\Http\Resources\RecipeTypeResource;
use App\Models\RecipeType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;

class RecipeTypeController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return RecipeTypeResource::collection(RecipeType::all());
    }

    public function show(RecipeType $recipeType): RecipeTypeResource
    {
        return new RecipeTypeResource($recipeType);
    }

    public function store(StoreRecipeTypeRequest $request): RecipeTypeResource
    {
        $data = $request->validated();
        $data['slug'] = Str::slug($data['name']);

        $recipeType = RecipeType::create($data);

        return new RecipeTypeResource($recipeType);
    }

    public function update(UpdateRecipeTypeRequest $request, RecipeType $recipeType): RecipeTypeResource
    {
        $data = $request->validated();

        if (isset($data['name'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        $recipeType->update($data);

        return new RecipeTypeResource($recipeType);
    }

    /**
     * @response 204
     */
    public function destroy(RecipeType $recipeType): JsonResponse
    {
        $recipeType->delete();

        return response()->json(null, 204);
    }
}
