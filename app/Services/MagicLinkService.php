<?php

namespace App\Services;

use App\Models\MagicLink;
use App\Models\User;
use App\Notifications\MagicLinkNotification;
use Illuminate\Support\Str;

class MagicLinkService
{
    private int $expiresInMinutes;

    public function __construct()
    {
        $this->expiresInMinutes = (int) config('auth.magic_link_expires_minutes', 15);
    }

    public function requestLink(string $email): void
    {
        $user = User::firstOrCreate(
            ['email' => $email],
            ['name' => Str::before($email, '@')]
        );

        // Invalidate previous unused links
        MagicLink::where('user_id', $user->id)
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->delete();

        $token = Str::random(64);

        MagicLink::create([
            'user_id' => $user->id,
            'token' => $token,
            'expires_at' => now()->addMinutes($this->expiresInMinutes),
            'created_at' => now(),
        ]);

        $user->notify(new MagicLinkNotification($token, $this->expiresInMinutes));
    }

    public function consumeLink(string $token): ?string
    {
        $magicLink = MagicLink::where('token', $token)
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->first();

        if (! $magicLink) {
            return null;
        }

        $magicLink->update(['used_at' => now()]);

        $user = $magicLink->user;

        if (! $user->email_verified_at) {
            $user->update(['email_verified_at' => now()]);
        }

        return $user->createToken('magic-link')->plainTextToken;
    }
}
