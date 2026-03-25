<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function ban(Request $request, User $user): JsonResponse
    {
        abort_if($user->is($request->user()), 422, 'Cannot ban yourself.');
        abort_if($user->is_admin, 422, 'Cannot ban another admin.');

        $user->update(['banned_at' => now()]);

        return response()->json(null, 204);
    }

    public function unban(User $user): JsonResponse
    {
        $user->update(['banned_at' => null]);

        return response()->json(null, 204);
    }
}
