<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminBanTest extends TestCase
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

    public function test_admin_can_ban_a_user(): void
    {
        $admin = $this->admin();
        $target = $this->user();

        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/admin/users/{$target->id}/ban")
            ->assertStatus(204);

        $this->assertNotNull($target->fresh()->banned_at);
    }

    public function test_banned_user_cannot_access_authenticated_routes(): void
    {
        $banned = User::factory()->create(['banned_at' => now()]);

        $this->actingAs($banned, 'sanctum')
            ->getJson('/api/v1/users/me')
            ->assertStatus(403)
            ->assertJsonFragment(['message' => 'Account suspended.']);
    }

    public function test_admin_can_unban_a_user(): void
    {
        $admin = $this->admin();
        $banned = User::factory()->create(['banned_at' => now()]);

        $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/v1/admin/users/{$banned->id}/ban")
            ->assertStatus(204);

        $this->assertNull($banned->fresh()->banned_at);
    }

    public function test_unbanned_user_can_access_authenticated_routes_again(): void
    {
        $user = User::factory()->create(['banned_at' => now()]);
        $user->update(['banned_at' => null]);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/users/me')
            ->assertStatus(200);
    }

    public function test_admin_cannot_ban_themselves(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/admin/users/{$admin->id}/ban")
            ->assertStatus(422);

        $this->assertNull($admin->fresh()->banned_at);
    }

    public function test_admin_cannot_ban_another_admin(): void
    {
        $admin = $this->admin();
        $otherAdmin = $this->admin();

        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/admin/users/{$otherAdmin->id}/ban")
            ->assertStatus(422);

        $this->assertNull($otherAdmin->fresh()->banned_at);
    }

    public function test_regular_user_cannot_ban_users(): void
    {
        $actor = $this->user();
        $target = $this->user();

        $this->actingAs($actor, 'sanctum')
            ->postJson("/api/v1/admin/users/{$target->id}/ban")
            ->assertStatus(403);
    }
}
