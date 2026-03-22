<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\MagicLinkRequest;
use App\Services\MagicLinkService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MagicLinkController extends Controller
{
    public function __construct(private readonly MagicLinkService $magicLinkService) {}

    /**
     * Request a magic link.
     *
     * Sends a magic link to the provided email. If no account exists, one is created.
     * Always returns 202 regardless of whether the email exists (security).
     */
    public function store(MagicLinkRequest $request): JsonResponse
    {
        $this->magicLinkService->requestLink($request->validated('email'));

        return response()->json([
            'message' => 'If an account exists for this email, a magic link has been sent.',
        ], 202);
    }

    /**
     * Consume a magic link token and return a Sanctum bearer token.
     *
     * @response 200 {"token": "1|abc123..."}
     * @response 422 {"message": "Invalid or expired token."}
     */
    public function show(string $token): JsonResponse
    {
        $bearerToken = $this->magicLinkService->consumeLink($token);

        if (! $bearerToken) {
            return response()->json(['message' => 'Invalid or expired token.'], 422);
        }

        return response()->json(['token' => $bearerToken]);
    }

    /**
     * Revoke the current Sanctum token (logout).
     *
     * @response 204
     */
    public function destroy(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(null, 204);
    }
}
