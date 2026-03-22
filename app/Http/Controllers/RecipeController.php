<?php

namespace App\Http\Controllers;

use App\Http\Requests\Recipe\StoreRecipeRequest;
use App\Http\Requests\Recipe\UpdateRecipeRequest;
use App\Http\Resources\RecipeResource;
use App\Models\Recipe;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RecipeController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $recipes = Recipe::public()
            ->with(['user', 'brewMethod', 'recipeType'])
            ->when($request->filled('brew_method_id'), fn ($q) => $q->where('brew_method_id', $request->brew_method_id))
            ->when($request->filled('recipe_type_id'), fn ($q) => $q->where('recipe_type_id', $request->recipe_type_id))
            ->latest()
            ->paginate(15);

        return RecipeResource::collection($recipes);
    }

    public function show(Recipe $recipe): RecipeResource
    {
        if ($recipe->visibility->value === 'private') {
            $user = auth('sanctum')->user();
            if (! $user || $user->id !== $recipe->user_id) {
                abort(404);
            }
        }

        $recipe->load(['user', 'brewMethod', 'recipeType', 'steps', 'ingredients', 'equipment']);

        return new RecipeResource($recipe);
    }

    public function store(StoreRecipeRequest $request): RecipeResource
    {
        $data = $request->validated();

        $recipe = DB::transaction(function () use ($data, $request) {
            $recipe = $request->user()->recipes()->create([
                'brew_method_id' => $data['brew_method_id'],
                'recipe_type_id' => $data['recipe_type_id'],
                'title' => $data['title'],
                'slug' => Str::slug($data['title']).'-'.Str::random(6),
                'description' => $data['description'] ?? null,
                'coffee_grams' => $data['coffee_grams'],
                'water_ml' => $data['water_ml'] ?? null,
                'yield_ml' => $data['yield_ml'] ?? null,
                'brew_time_seconds' => $data['brew_time_seconds'],
                'visibility' => $data['visibility'] ?? 'public',
            ]);

            $this->syncSteps($recipe, $data['steps'] ?? []);
            $this->syncIngredients($recipe, $data['ingredients'] ?? []);

            return $recipe;
        });

        $recipe->load(['user', 'brewMethod', 'recipeType', 'steps', 'ingredients', 'equipment']);

        return new RecipeResource($recipe);
    }

    public function update(UpdateRecipeRequest $request, Recipe $recipe): RecipeResource
    {
        $this->authorize('update', $recipe);

        $data = $request->validated();

        DB::transaction(function () use ($recipe, $data) {
            $fields = array_filter([
                'brew_method_id' => $data['brew_method_id'] ?? null,
                'recipe_type_id' => $data['recipe_type_id'] ?? null,
                'title' => $data['title'] ?? null,
                'description' => array_key_exists('description', $data) ? $data['description'] : null,
                'coffee_grams' => $data['coffee_grams'] ?? null,
                'water_ml' => array_key_exists('water_ml', $data) ? $data['water_ml'] : null,
                'yield_ml' => array_key_exists('yield_ml', $data) ? $data['yield_ml'] : null,
                'brew_time_seconds' => $data['brew_time_seconds'] ?? null,
                'visibility' => $data['visibility'] ?? null,
            ], fn ($v) => $v !== null);

            if (isset($data['title'])) {
                $fields['slug'] = Str::slug($data['title']).'-'.Str::random(6);
            }

            $recipe->update($fields);

            if (array_key_exists('steps', $data)) {
                $this->syncSteps($recipe, $data['steps']);
            }

            if (array_key_exists('ingredients', $data)) {
                $this->syncIngredients($recipe, $data['ingredients']);
            }
        });

        $recipe->load(['user', 'brewMethod', 'recipeType', 'steps', 'ingredients', 'equipment']);

        return new RecipeResource($recipe);
    }

    /**
     * @response 204
     */
    public function destroy(Recipe $recipe): JsonResponse
    {
        $this->authorize('delete', $recipe);

        $recipe->delete();

        return response()->json(null, 204);
    }

    public function updateVisibility(Request $request, Recipe $recipe): RecipeResource
    {
        $this->authorize('updateVisibility', $recipe);

        $request->validate([
            'visibility' => ['required', 'in:public,private'],
        ]);

        $recipe->update(['visibility' => $request->visibility]);

        return new RecipeResource($recipe);
    }

    public function myRecipes(Request $request): AnonymousResourceCollection
    {
        $recipes = $request->user()
            ->recipes()
            ->with(['brewMethod', 'recipeType'])
            ->latest()
            ->paginate(15);

        return RecipeResource::collection($recipes);
    }

    private function syncSteps(Recipe $recipe, array $steps): void
    {
        $recipe->steps()->delete();

        if (empty($steps)) {
            return;
        }

        $recipe->steps()->createMany($steps);
    }

    private function syncIngredients(Recipe $recipe, array $ingredients): void
    {
        $syncData = [];
        foreach ($ingredients as $ingredient) {
            $syncData[$ingredient['id']] = [
                'quantity' => $ingredient['quantity'],
                'unit' => $ingredient['unit'],
            ];
        }

        $recipe->ingredients()->sync($syncData);
    }
}
