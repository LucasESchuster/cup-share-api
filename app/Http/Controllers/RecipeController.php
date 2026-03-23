<?php

namespace App\Http\Controllers;

use App\Http\Requests\Recipe\StoreRecipeRequest;
use App\Http\Requests\Recipe\UpdateRecipeRequest;
use App\Http\Resources\RecipeResource;
use App\Models\Like;
use App\Models\Recipe;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Sanctum\PersonalAccessToken;

class RecipeController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $recipes = Recipe::public()
            ->with(['user', 'brewMethod'])
            ->when($request->filled('brew_method_id'), fn ($q) => $q->where('brew_method_id', $request->brew_method_id))
            ->latest()
            ->paginate(15);

        $user = null;
        if ($token = $request->bearerToken()) {
            $user = PersonalAccessToken::findToken($token)?->tokenable;
        }

        if ($user) {
            $likedIds = Like::where('user_id', $user->id)
                ->whereIn('recipe_id', $recipes->pluck('id'))
                ->pluck('recipe_id')
                ->all();

            $recipes->each(fn ($recipe) => $recipe->setAttribute('liked_by_me', in_array($recipe->id, $likedIds)));
        }

        return RecipeResource::collection($recipes);
    }

    public function show(Recipe $recipe): RecipeResource
    {
        $authUser = auth('sanctum')->user();

        if ($recipe->visibility->value === 'private') {
            if (! $authUser || $authUser->id !== $recipe->user_id) {
                abort(404);
            }
        }

        $recipe->load(['user', 'brewMethod', 'steps', 'equipmentEntries.equipment']);

        $recipe->setAttribute(
            'liked_by_me',
            $authUser && $recipe->likes()->where('user_id', $authUser->id)->exists()
        );

        return new RecipeResource($recipe);
    }

    public function store(StoreRecipeRequest $request): RecipeResource
    {
        $data = $request->validated();

        $recipe = DB::transaction(function () use ($data, $request) {
            $recipe = $request->user()->recipes()->create([
                'brew_method_id'            => $data['brew_method_id'],
                'title'                     => $data['title'],
                'slug'                      => Str::slug($data['title']).'-'.Str::random(6),
                'description'               => $data['description'] ?? null,
                'coffee_grams'              => $data['coffee_grams'],
                'water_ml'                  => $data['water_ml'] ?? null,
                'yield_ml'                  => $data['yield_ml'] ?? null,
                'brew_time_seconds'         => $data['brew_time_seconds'],
                'visibility'                => $data['visibility'] ?? 'public',
                'video_url'                 => $data['video_url'] ?? null,
                'water_temperature_celsius' => $data['water_temperature_celsius'] ?? null,
                'coffee_description'        => $data['coffee_description'] ?? null,
            ]);

            $this->syncSteps($recipe, $data['steps'] ?? []);

            return $recipe;
        });

        $recipe->load(['user', 'brewMethod', 'steps', 'equipmentEntries.equipment']);

        return new RecipeResource($recipe);
    }

    public function update(UpdateRecipeRequest $request, Recipe $recipe): RecipeResource
    {
        $this->authorize('update', $recipe);

        $data = $request->validated();

        DB::transaction(function () use ($recipe, $data) {
            $nullableKeys = ['description', 'water_ml', 'yield_ml', 'video_url', 'water_temperature_celsius', 'coffee_description'];

            $fields = array_filter([
                'brew_method_id'            => $data['brew_method_id'] ?? null,
                'title'                     => $data['title'] ?? null,
                'description'               => array_key_exists('description', $data) ? $data['description'] : null,
                'coffee_grams'              => $data['coffee_grams'] ?? null,
                'water_ml'                  => array_key_exists('water_ml', $data) ? $data['water_ml'] : null,
                'yield_ml'                  => array_key_exists('yield_ml', $data) ? $data['yield_ml'] : null,
                'brew_time_seconds'         => $data['brew_time_seconds'] ?? null,
                'visibility'                => $data['visibility'] ?? null,
                'video_url'                 => array_key_exists('video_url', $data) ? $data['video_url'] : null,
                'water_temperature_celsius' => array_key_exists('water_temperature_celsius', $data) ? $data['water_temperature_celsius'] : null,
                'coffee_description'        => array_key_exists('coffee_description', $data) ? $data['coffee_description'] : null,
            ], fn ($v, $k) => $v !== null || in_array($k, $nullableKeys), ARRAY_FILTER_USE_BOTH);

            if (isset($data['title'])) {
                $fields['slug'] = Str::slug($data['title']).'-'.Str::random(6);
            }

            $recipe->update($fields);

            if (array_key_exists('steps', $data)) {
                $this->syncSteps($recipe, $data['steps']);
            }

        });

        $recipe->load(['user', 'brewMethod', 'steps', 'equipmentEntries.equipment']);

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
            ->with(['brewMethod'])
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

}
