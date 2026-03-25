<?php

namespace Tests\Feature\Admin;

use App\Models\MagicLink;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminMagicLinkTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    private function user(): User
    {
        return User::factory()->create();
    }

    private function createMagicLink(User $user, array $attributes = []): MagicLink
    {
        return MagicLink::create(array_merge([
            'user_id'    => $user->id,
            'token'      => bin2hex(random_bytes(32)),
            'expires_at' => now()->addMinutes(15),
            'used_at'    => null,
        ], $attributes));
    }

    public function test_admin_can_list_all_magic_links(): void
    {
        $user = $this->user();
        $this->createMagicLink($user);
        $this->createMagicLink($user);

        $this->actingAs($this->admin(), 'sanctum')
            ->getJson('/api/v1/admin/magic-links')
            ->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_magic_link_has_correct_status_pending(): void
    {
        $user = $this->user();
        $this->createMagicLink($user, ['expires_at' => now()->addMinutes(15), 'used_at' => null]);

        $this->actingAs($this->admin(), 'sanctum')
            ->getJson('/api/v1/admin/magic-links')
            ->assertStatus(200)
            ->assertJsonFragment(['status' => 'pending']);
    }

    public function test_magic_link_has_correct_status_used(): void
    {
        $user = $this->user();
        $this->createMagicLink($user, ['used_at' => now()]);

        $this->actingAs($this->admin(), 'sanctum')
            ->getJson('/api/v1/admin/magic-links')
            ->assertStatus(200)
            ->assertJsonFragment(['status' => 'used']);
    }

    public function test_magic_link_has_correct_status_expired(): void
    {
        $user = $this->user();
        $this->createMagicLink($user, ['expires_at' => now()->subMinutes(1), 'used_at' => null]);

        $this->actingAs($this->admin(), 'sanctum')
            ->getJson('/api/v1/admin/magic-links')
            ->assertStatus(200)
            ->assertJsonFragment(['status' => 'expired']);
    }

    public function test_filter_by_pending_status(): void
    {
        $user = $this->user();
        $this->createMagicLink($user, ['expires_at' => now()->addMinutes(15), 'used_at' => null]);
        $this->createMagicLink($user, ['used_at' => now()]);
        $this->createMagicLink($user, ['expires_at' => now()->subMinutes(1), 'used_at' => null]);

        $this->actingAs($this->admin(), 'sanctum')
            ->getJson('/api/v1/admin/magic-links?status=pending')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['status' => 'pending']);
    }

    public function test_filter_by_used_status(): void
    {
        $user = $this->user();
        $this->createMagicLink($user, ['used_at' => now()]);
        $this->createMagicLink($user, ['expires_at' => now()->addMinutes(15), 'used_at' => null]);

        $this->actingAs($this->admin(), 'sanctum')
            ->getJson('/api/v1/admin/magic-links?status=used')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['status' => 'used']);
    }

    public function test_filter_by_expired_status(): void
    {
        $user = $this->user();
        $this->createMagicLink($user, ['expires_at' => now()->subMinutes(1), 'used_at' => null]);
        $this->createMagicLink($user, ['expires_at' => now()->addMinutes(15), 'used_at' => null]);

        $this->actingAs($this->admin(), 'sanctum')
            ->getJson('/api/v1/admin/magic-links?status=expired')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['status' => 'expired']);
    }

    public function test_response_includes_user_info(): void
    {
        $user = $this->user();
        $this->createMagicLink($user);

        $this->actingAs($this->admin(), 'sanctum')
            ->getJson('/api/v1/admin/magic-links')
            ->assertStatus(200)
            ->assertJsonFragment([
                'id'    => $user->id,
                'email' => $user->email,
                'name'  => $user->name,
            ]);
    }

    public function test_regular_user_cannot_access_magic_links(): void
    {
        $this->actingAs($this->user(), 'sanctum')
            ->getJson('/api/v1/admin/magic-links')
            ->assertStatus(403);
    }

    public function test_unauthenticated_cannot_access_magic_links(): void
    {
        $this->getJson('/api/v1/admin/magic-links')
            ->assertStatus(401);
    }
}
