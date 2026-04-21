<?php

namespace Tests\Feature\E2E;

use App\Models\BrewMethod;
use App\Models\Recipe;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthorizationFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_receives_401_on_protected_user_routes(): void
    {
        $this->getJson('/api/v1/users/me')->assertStatus(401);
        $this->putJson('/api/v1/users/me', ['name' => 'x'])->assertStatus(401);
        $this->deleteJson('/api/v1/users/me')->assertStatus(401);
        $this->getJson('/api/v1/users/me/recipes')->assertStatus(401);
        $this->deleteJson('/api/v1/auth/logout')->assertStatus(401);
    }

    public function test_guest_receives_401_on_protected_recipe_routes(): void
    {
        $brewMethod = BrewMethod::create(['name' => 'V60', 'slug' => 'v60', 'category' => 'filter']);
        $recipe = Recipe::factory()->create([
            'brew_method_id' => $brewMethod->id,
            'visibility' => 'public',
        ]);

        $this->postJson('/api/v1/recipes', [])->assertStatus(401);
        $this->putJson("/api/v1/recipes/{$recipe->id}", [])->assertStatus(401);
        $this->deleteJson("/api/v1/recipes/{$recipe->id}")->assertStatus(401);
        $this->patchJson("/api/v1/recipes/{$recipe->id}/visibility", ['visibility' => 'public'])->assertStatus(401);
        $this->postJson("/api/v1/recipes/{$recipe->id}/likes")->assertStatus(401);
        $this->deleteJson("/api/v1/recipes/{$recipe->id}/likes")->assertStatus(401);
        $this->postJson("/api/v1/recipes/{$recipe->id}/equipment", [])->assertStatus(401);
    }

    public function test_user_receives_403_editing_anothers_recipe(): void
    {
        $brewMethod = BrewMethod::create(['name' => 'V60', 'slug' => 'v60', 'category' => 'filter']);
        $owner = User::factory()->create();
        $attacker = User::factory()->create();

        $recipe = Recipe::factory()->create([
            'user_id' => $owner->id,
            'brew_method_id' => $brewMethod->id,
        ]);

        $this->actingAs($attacker, 'sanctum')
            ->putJson("/api/v1/recipes/{$recipe->id}", ['title' => 'Hack'])
            ->assertStatus(403);

        $this->actingAs($attacker, 'sanctum')
            ->deleteJson("/api/v1/recipes/{$recipe->id}")
            ->assertStatus(403);

        $this->actingAs($attacker, 'sanctum')
            ->patchJson("/api/v1/recipes/{$recipe->id}/visibility", ['visibility' => 'private'])
            ->assertStatus(403);

        $this->actingAs($attacker, 'sanctum')
            ->postJson("/api/v1/recipes/{$recipe->id}/equipment", ['custom_name' => 'Hack'])
            ->assertStatus(403);
    }

    public function test_regular_user_receives_403_on_admin_routes(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/admin/brew-methods', ['name' => 'X'])
            ->assertStatus(403);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/admin/equipment', ['name' => 'X', 'type' => 'other'])
            ->assertStatus(403);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/admin/users')
            ->assertStatus(403);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/admin/magic-links')
            ->assertStatus(403);
    }

    public function test_guest_receives_401_on_admin_routes(): void
    {
        $this->postJson('/api/v1/admin/brew-methods', [])->assertStatus(401);
        $this->postJson('/api/v1/admin/equipment', [])->assertStatus(401);
        $this->getJson('/api/v1/admin/users')->assertStatus(401);
        $this->getJson('/api/v1/admin/magic-links')->assertStatus(401);
    }

    public function test_admin_can_access_admin_routes(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/users')
            ->assertStatus(200);

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/magic-links')
            ->assertStatus(200);
    }
}
