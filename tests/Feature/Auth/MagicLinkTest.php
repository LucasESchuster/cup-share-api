<?php

namespace Tests\Feature\Auth;

use App\Models\MagicLink;
use App\Models\User;
use App\Notifications\MagicLinkNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class MagicLinkTest extends TestCase
{
    use RefreshDatabase;

    public function test_request_magic_link_creates_user_if_not_exists(): void
    {
        Notification::fake();

        $this->postJson('/api/v1/auth/magic-link', ['email' => 'novo@example.com'])
            ->assertStatus(202);

        $this->assertDatabaseHas('users', ['email' => 'novo@example.com']);
        Notification::assertSentTo(User::where('email', 'novo@example.com')->first(), MagicLinkNotification::class);
    }

    public function test_request_magic_link_sends_to_existing_user(): void
    {
        Notification::fake();

        $user = User::factory()->create(['email' => 'existente@example.com']);

        $this->postJson('/api/v1/auth/magic-link', ['email' => $user->email])
            ->assertStatus(202);

        Notification::assertSentTo($user, MagicLinkNotification::class);
    }

    public function test_request_magic_link_requires_valid_email(): void
    {
        $this->postJson('/api/v1/auth/magic-link', ['email' => 'nao-é-email'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_consume_valid_token_returns_bearer_token(): void
    {
        $user = User::factory()->create();

        $magicLink = MagicLink::create([
            'user_id' => $user->id,
            'token' => 'token-valido-123',
            'expires_at' => now()->addMinutes(15),
            'created_at' => now(),
        ]);

        $response = $this->getJson('/api/v1/auth/magic-link/token-valido-123')
            ->assertStatus(200)
            ->assertJsonStructure(['token']);

        $this->assertNotNull($response->json('token'));
        $this->assertNotNull(MagicLink::where('token', 'token-valido-123')->value('used_at'));
    }

    public function test_consume_expired_token_returns_422(): void
    {
        $user = User::factory()->create();

        MagicLink::create([
            'user_id' => $user->id,
            'token' => 'token-expirado',
            'expires_at' => now()->subMinutes(1),
            'created_at' => now()->subMinutes(16),
        ]);

        $this->getJson('/api/v1/auth/magic-link/token-expirado')
            ->assertStatus(422);
    }

    public function test_consume_used_token_returns_422(): void
    {
        $user = User::factory()->create();

        MagicLink::create([
            'user_id' => $user->id,
            'token' => 'token-usado',
            'expires_at' => now()->addMinutes(15),
            'used_at' => now()->subMinutes(5),
            'created_at' => now()->subMinutes(10),
        ]);

        $this->getJson('/api/v1/auth/magic-link/token-usado')
            ->assertStatus(422);
    }

    public function test_logout_revokes_token(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $this->withToken($token)
            ->deleteJson('/api/v1/auth/logout')
            ->assertStatus(204);

        // Token should be revoked — refresh app to clear auth cache
        $this->refreshApplication();

        $this->withToken($token)
            ->getJson('/api/v1/users/me')
            ->assertStatus(401);
    }
}
