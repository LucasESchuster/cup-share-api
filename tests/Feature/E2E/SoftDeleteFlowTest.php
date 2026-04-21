<?php

namespace Tests\Feature\E2E;

use App\Models\BrewMethod;
use App\Models\Recipe;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SoftDeleteFlowTest extends TestCase
{
    use RefreshDatabase;

    private BrewMethod $brewMethod;

    protected function setUp(): void
    {
        parent::setUp();

        $this->brewMethod = BrewMethod::create(['name' => 'V60', 'slug' => 'v60', 'category' => 'filter']);
    }

    public function test_deleted_recipe_is_removed_from_public_feed_and_returns_404(): void
    {
        $author = User::factory()->create();
        $recipe = Recipe::factory()->create([
            'user_id' => $author->id,
            'brew_method_id' => $this->brewMethod->id,
            'visibility' => 'public',
            'title' => 'Será deletada',
        ]);

        $this->getJson('/api/v1/recipes')
            ->assertStatus(200)
            ->assertJsonFragment(['id' => $recipe->id]);

        $this->actingAs($author, 'sanctum')
            ->deleteJson("/api/v1/recipes/{$recipe->id}")
            ->assertStatus(204);

        $this->getJson('/api/v1/recipes')
            ->assertStatus(200)
            ->assertJsonMissing(['id' => $recipe->id]);

        $this->getJson("/api/v1/recipes/{$recipe->id}")
            ->assertStatus(404);

        $this->assertSoftDeleted('recipes', ['id' => $recipe->id]);
    }

    public function test_deleted_user_account_is_soft_deleted(): void
    {
        $user = User::factory()->create();
        $bearer = $user->createToken('test')->plainTextToken;

        $this->withToken($bearer)
            ->getJson('/api/v1/users/me')
            ->assertStatus(200);

        $this->withToken($bearer)
            ->deleteJson('/api/v1/users/me')
            ->assertStatus(204);

        $this->assertSoftDeleted('users', ['id' => $user->id]);
    }

    public function test_deleted_user_cannot_reuse_previous_bearer_token(): void
    {
        $user = User::factory()->create();
        $bearer = $user->createToken('test')->plainTextToken;

        $this->withToken($bearer)
            ->deleteJson('/api/v1/users/me')
            ->assertStatus(204);

        $this->refreshApplication();

        $this->withToken($bearer)
            ->getJson('/api/v1/users/me')
            ->assertStatus(401);
    }

    public function test_admin_deleted_equipment_disappears_from_public_listing(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $created = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/admin/equipment', [
                'name' => 'Equipamento para deletar',
                'type' => 'scale',
            ])
            ->assertStatus(201);

        $equipmentId = $created->json('data.id');

        $this->getJson('/api/v1/equipment')
            ->assertJsonFragment(['id' => $equipmentId]);

        $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/v1/admin/equipment/{$equipmentId}")
            ->assertStatus(204);

        $this->getJson('/api/v1/equipment')
            ->assertStatus(200)
            ->assertJsonMissing(['id' => $equipmentId]);

        $this->assertSoftDeleted('equipment', ['id' => $equipmentId]);
    }

    public function test_deleted_recipe_keeps_likes_records_but_is_inaccessible(): void
    {
        $author = User::factory()->create();
        $liker = User::factory()->create();
        $recipe = Recipe::factory()->create([
            'user_id' => $author->id,
            'brew_method_id' => $this->brewMethod->id,
            'visibility' => 'public',
        ]);

        $this->actingAs($liker, 'sanctum')
            ->postJson("/api/v1/recipes/{$recipe->id}/likes")
            ->assertStatus(201);

        $this->actingAs($author, 'sanctum')
            ->deleteJson("/api/v1/recipes/{$recipe->id}")
            ->assertStatus(204);

        $this->getJson("/api/v1/recipes/{$recipe->id}/likes")
            ->assertStatus(404);
    }
}
