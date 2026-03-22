<?php

namespace App\Http\Controllers;

use App\Http\Requests\Ingredient\StoreIngredientRequest;
use App\Http\Requests\Ingredient\UpdateIngredientRequest;
use App\Http\Resources\IngredientResource;
use App\Models\Ingredient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;

class IngredientController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $ingredients = Ingredient::when(
            $request->filled('search'),
            fn ($q) => $q->where('name', 'ilike', '%'.$request->search.'%')
        )->paginate(20);

        return IngredientResource::collection($ingredients);
    }

    public function show(Ingredient $ingredient): IngredientResource
    {
        return new IngredientResource($ingredient);
    }

    public function store(StoreIngredientRequest $request): IngredientResource
    {
        $data = $request->validated();
        $data['slug'] = Str::slug($data['name']);

        $ingredient = Ingredient::create($data);

        return new IngredientResource($ingredient);
    }

    public function update(UpdateIngredientRequest $request, Ingredient $ingredient): IngredientResource
    {
        $data = $request->validated();

        if (isset($data['name'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        $ingredient->update($data);

        return new IngredientResource($ingredient);
    }

    /**
     * @response 204
     */
    public function destroy(Ingredient $ingredient): JsonResponse
    {
        $ingredient->delete();

        return response()->json(null, 204);
    }
}
