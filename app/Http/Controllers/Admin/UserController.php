<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class UserController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return UserResource::collection(User::paginate(20));
    }

    public function ban(Request $request, User $user): JsonResponse
    {
        abort_if($user->is($request->user()), 422, 'Cannot ban yourself.');
        abort_if($user->is_admin, 422, 'Cannot ban another admin.');

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $user->update([
            'banned_at'  => now(),
            'ban_reason' => $validated['reason'],
        ]);

        return response()->json(null, 204);
    }

    public function unban(User $user): JsonResponse
    {
        $user->update(['banned_at' => null, 'ban_reason' => null]);

        return response()->json(null, 204);
    }
}
