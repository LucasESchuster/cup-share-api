<?php

namespace Tests\Feature\E2E;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BanFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_bans_user_and_banned_user_receives_403_with_ban_payload(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $target = User::factory()->create();

        $this->actingAs($target, 'sanctum')
            ->getJson('/api/v1/users/me')
            ->assertStatus(200);

        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/admin/users/{$target->id}/ban", [
                'reason' => 'Conteúdo abusivo.',
            ])
            ->assertStatus(204);

        $this->actingAs($target->fresh(), 'sanctum')
            ->getJson('/api/v1/users/me')
            ->assertStatus(403)
            ->assertJsonStructure(['message', 'banned_at', 'ban_reason'])
            ->assertJsonFragment([
                'message' => 'Account suspended.',
                'ban_reason' => 'Conteúdo abusivo.',
            ]);
    }

    public function test_banned_user_regains_access_after_unban(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $target = User::factory()->create();

        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/admin/users/{$target->id}/ban", ['reason' => 'Teste.'])
            ->assertStatus(204);

        $this->actingAs($target->fresh(), 'sanctum')
            ->getJson('/api/v1/users/me')
            ->assertStatus(403);

        $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/v1/admin/users/{$target->id}/ban")
            ->assertStatus(204);

        $this->actingAs($target->fresh(), 'sanctum')
            ->getJson('/api/v1/users/me')
            ->assertStatus(200)
            ->assertJsonFragment(['id' => $target->id]);
    }

    public function test_banned_user_cannot_create_recipe_or_like(): void
    {
        $brewMethod = \App\Models\BrewMethod::create(['name' => 'V60', 'slug' => 'v60', 'category' => 'filter']);
        $author = User::factory()->create();
        $recipe = \App\Models\Recipe::factory()->create([
            'user_id' => $author->id,
            'brew_method_id' => $brewMethod->id,
            'visibility' => 'public',
        ]);

        $banned = User::factory()->create([
            'banned_at' => now(),
            'ban_reason' => 'Spam.',
        ]);

        $this->actingAs($banned, 'sanctum')
            ->postJson('/api/v1/recipes', [])
            ->assertStatus(403)
            ->assertJsonFragment(['message' => 'Account suspended.']);

        $this->actingAs($banned, 'sanctum')
            ->postJson("/api/v1/recipes/{$recipe->id}/likes")
            ->assertStatus(403)
            ->assertJsonFragment(['message' => 'Account suspended.']);

        $this->actingAs($banned, 'sanctum')
            ->deleteJson('/api/v1/auth/logout')
            ->assertStatus(403);
    }

    public function test_ban_response_includes_banned_at_timestamp(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $target = User::factory()->create();

        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/admin/users/{$target->id}/ban", ['reason' => 'Motivo.'])
            ->assertStatus(204);

        $response = $this->actingAs($target->fresh(), 'sanctum')
            ->getJson('/api/v1/users/me')
            ->assertStatus(403);

        $this->assertNotNull($response->json('banned_at'));
        $this->assertEquals('Motivo.', $response->json('ban_reason'));
    }

    public function test_public_routes_remain_accessible_to_banned_users(): void
    {
        User::factory()->create([
            'banned_at' => now(),
            'ban_reason' => 'Spam.',
        ]);

        $this->getJson('/api/v1/recipes')->assertStatus(200);
        $this->getJson('/api/v1/brew-methods')->assertStatus(200);
        $this->getJson('/api/v1/equipment')->assertStatus(200);
    }
}
