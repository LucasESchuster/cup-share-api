<?php

namespace Tests\Feature\E2E;

use App\Models\MagicLink;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class MagicLinkEdgeCasesFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_expired_magic_link_is_rejected_after_15_minutes(): void
    {
        Carbon::setTestNow('2026-04-20 10:00:00');

        $this->postJson('/api/v1/auth/magic-link', ['email' => 'expire@example.com'])
            ->assertStatus(202);

        $token = MagicLink::latest('id')->value('token');
        $this->assertNotNull($token);

        Carbon::setTestNow('2026-04-20 10:16:00');

        $this->getJson("/api/v1/auth/magic-link/{$token}")
            ->assertStatus(422)
            ->assertJsonFragment(['message' => 'Invalid or expired token.']);

        Carbon::setTestNow();
    }

    public function test_valid_magic_link_still_works_before_expiration(): void
    {
        Carbon::setTestNow('2026-04-20 10:00:00');

        $this->postJson('/api/v1/auth/magic-link', ['email' => 'valid@example.com'])
            ->assertStatus(202);

        $token = MagicLink::latest('id')->value('token');

        Carbon::setTestNow('2026-04-20 10:14:00');

        $this->getJson("/api/v1/auth/magic-link/{$token}")
            ->assertStatus(200)
            ->assertJsonStructure(['token']);

        Carbon::setTestNow();
    }

    public function test_consumed_magic_link_cannot_be_reused(): void
    {
        $this->postJson('/api/v1/auth/magic-link', ['email' => 'once@example.com'])
            ->assertStatus(202);

        $token = MagicLink::latest('id')->value('token');

        $this->getJson("/api/v1/auth/magic-link/{$token}")
            ->assertStatus(200);

        $this->getJson("/api/v1/auth/magic-link/{$token}")
            ->assertStatus(422)
            ->assertJsonFragment(['message' => 'Invalid or expired token.']);
    }

    public function test_invalid_token_returns_422(): void
    {
        $this->getJson('/api/v1/auth/magic-link/inexistente-token-xyz-9999')
            ->assertStatus(422)
            ->assertJsonFragment(['message' => 'Invalid or expired token.']);
    }

    public function test_new_magic_link_request_invalidates_previous_pending_tokens(): void
    {
        $email = 'invalidation@example.com';

        $this->postJson('/api/v1/auth/magic-link', ['email' => $email])
            ->assertStatus(202);

        $firstToken = MagicLink::latest('id')->value('token');
        $this->assertDatabaseHas('magic_links', ['token' => $firstToken]);

        $this->postJson('/api/v1/auth/magic-link', ['email' => $email])
            ->assertStatus(202);

        $secondToken = MagicLink::latest('id')->value('token');
        $this->assertNotEquals($firstToken, $secondToken);

        $this->assertDatabaseMissing('magic_links', ['token' => $firstToken]);

        $this->getJson("/api/v1/auth/magic-link/{$firstToken}")
            ->assertStatus(422);

        $this->getJson("/api/v1/auth/magic-link/{$secondToken}")
            ->assertStatus(200);
    }

    public function test_logout_revokes_sanctum_token_from_magic_link(): void
    {
        $this->postJson('/api/v1/auth/magic-link', ['email' => 'logout@example.com'])
            ->assertStatus(202);

        $mlToken = MagicLink::latest('id')->value('token');

        $bearer = $this->getJson("/api/v1/auth/magic-link/{$mlToken}")
            ->assertStatus(200)
            ->json('token');

        $this->withToken($bearer)
            ->getJson('/api/v1/users/me')
            ->assertStatus(200);

        $this->withToken($bearer)
            ->deleteJson('/api/v1/auth/logout')
            ->assertStatus(204);

        $this->refreshApplication();

        $this->withToken($bearer)
            ->getJson('/api/v1/users/me')
            ->assertStatus(401);
    }

    public function test_magic_link_request_invalid_email_returns_422(): void
    {
        $this->postJson('/api/v1/auth/magic-link', ['email' => 'not-an-email'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_magic_link_always_returns_202_for_security_even_if_email_new(): void
    {
        $this->assertDatabaseMissing('users', ['email' => 'security@example.com']);

        $this->postJson('/api/v1/auth/magic-link', ['email' => 'security@example.com'])
            ->assertStatus(202);

        $this->assertDatabaseHas('users', ['email' => 'security@example.com']);
    }
}
