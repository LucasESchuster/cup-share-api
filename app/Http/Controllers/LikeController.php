<?php

namespace App\Http\Controllers;

use App\Models\Recipe;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

class LikeController extends Controller
{
    /**
     * Get like count and auth user's like status.
     *
     * @response 200 {"likes_count": 42, "liked_by_me": false}
     */
    public function show(Request $request, Recipe $recipe): JsonResponse
    {
        if ($recipe->visibility->value === 'private') {
            abort(404);
        }

        $user = null;
        if ($token = $request->bearerToken()) {
            $user = PersonalAccessToken::findToken($token)?->tokenable;
        }

        return response()->json([
            'likes_count' => $recipe->likes_count,
            'liked_by_me' => $user
                ? $recipe->likes()->where('user_id', $user->id)->exists()
                : false,
        ]);
    }

    public function store(Request $request, Recipe $recipe): JsonResponse
    {
        if ($recipe->visibility->value === 'private') {
            abort(404);
        }

        if ($recipe->user_id === $request->user()->id) {
            return response()->json(['message' => 'You cannot like your own recipe.'], 403);
        }

        $alreadyLiked = $recipe->likes()->where('user_id', $request->user()->id)->exists();

        if ($alreadyLiked) {
            return response()->json(['message' => 'You have already liked this recipe.'], 422);
        }

        $recipe->likes()->create(['user_id' => $request->user()->id]);
        $recipe->increment('likes_count');

        return response()->json(['likes_count' => $recipe->likes_count], 201);
    }

    /**
     * @response 204
     */
    public function destroy(Request $request, Recipe $recipe): JsonResponse
    {
        $deleted = $recipe->likes()->where('user_id', $request->user()->id)->delete();

        if ($deleted) {
            $recipe->decrement('likes_count');
        }

        return response()->json(null, 204);
    }
}
