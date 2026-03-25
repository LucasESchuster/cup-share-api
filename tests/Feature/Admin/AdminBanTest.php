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
            ->postJson("/api/v1/admin/users/{$target->id}/ban", [
                'reason' => 'Violação dos termos de uso.',
            ])
            ->assertStatus(204);

        $fresh = $target->fresh();
        $this->assertNotNull($fresh->banned_at);
        $this->assertEquals('Violação dos termos de uso.', $fresh->ban_reason);
    }

    public function test_ban_requires_reason(): void
    {
        $admin = $this->admin();
        $target = $this->user();

        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/admin/users/{$target->id}/ban", [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['reason']);

        $this->assertNull($target->fresh()->banned_at);
    }

    public function test_unban_clears_ban_reason(): void
    {
        $admin = $this->admin();
        $banned = User::factory()->create([
            'banned_at'  => now(),
            'ban_reason' => 'Spam.',
        ]);

        $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/v1/admin/users/{$banned->id}/ban")
            ->assertStatus(204);

        $fresh = $banned->fresh();
        $this->assertNull($fresh->banned_at);
        $this->assertNull($fresh->ban_reason);
    }

    public function test_banned_user_cannot_access_authenticated_routes(): void
    {
        $banned = User::factory()->create([
            'banned_at'  => now(),
            'ban_reason' => 'Spam.',
        ]);

        $this->actingAs($banned, 'sanctum')
            ->getJson('/api/v1/users/me')
            ->assertStatus(403)
            ->assertJsonStructure(['message', 'banned_at', 'ban_reason'])
            ->assertJsonFragment([
                'message'    => 'Account suspended.',
                'ban_reason' => 'Spam.',
            ]);
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
            ->postJson("/api/v1/admin/users/{$admin->id}/ban", ['reason' => 'Test.'])
            ->assertStatus(422);

        $this->assertNull($admin->fresh()->banned_at);
    }

    public function test_admin_cannot_ban_another_admin(): void
    {
        $admin = $this->admin();
        $otherAdmin = $this->admin();

        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/admin/users/{$otherAdmin->id}/ban", ['reason' => 'Test.'])
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

    public function test_admin_can_list_users(): void
    {
        $admin = $this->admin();
        User::factory(3)->create();

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/users')
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [['id', 'name', 'email', 'is_admin', 'banned_at', 'created_at']],
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            ]);
    }

    public function test_user_list_includes_banned_at_field(): void
    {
        $admin = $this->admin();
        User::factory()->create(['banned_at' => now()]);

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/users')
            ->assertStatus(200);

        $bannedUser = collect($response->json('data'))->firstWhere('banned_at', '!==', null);
        $this->assertNotNull($bannedUser);
        $this->assertNotNull($bannedUser['banned_at']);
    }

    public function test_regular_user_cannot_list_users(): void
    {
        $this->actingAs($this->user(), 'sanctum')
            ->getJson('/api/v1/admin/users')
            ->assertStatus(403);
    }

    public function test_unauthenticated_cannot_list_users(): void
    {
        $this->getJson('/api/v1/admin/users')
            ->assertStatus(401);
    }
}
