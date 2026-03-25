<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsNotBanned
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() && $request->user()->banned_at !== null) {
            $user = $request->user();

            return response()->json([
                'message'    => 'Account suspended.',
                'banned_at'  => $user->banned_at,
                'ban_reason' => $user->ban_reason,
            ], 403);
        }

        return $next($request);
    }
}
