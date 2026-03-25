<?php

namespace Tests\Feature\Admin;

use App\Enums\BrewMethodCategory;
use App\Models\BrewMethod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminBrewMethodTest extends TestCase
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

    public function test_admin_can_create_brew_method(): void
    {
        $this->actingAs($this->admin(), 'sanctum')
            ->postJson('/api/v1/admin/brew-methods', [
                'name'        => 'AeroPress',
                'description' => 'Pressure brewing',
                'category'    => BrewMethodCategory::Pressure->value,
            ])
            ->assertStatus(201)
            ->assertJsonFragment(['name' => 'AeroPress']);

        $this->assertDatabaseHas('brew_methods', ['name' => 'AeroPress']);
    }

    public function test_admin_can_update_brew_method(): void
    {
        $brewMethod = BrewMethod::create([
            'name'     => 'Old Name',
            'slug'     => 'old-name',
            'category' => BrewMethodCategory::Filter->value,
        ]);

        $this->actingAs($this->admin(), 'sanctum')
            ->putJson("/api/v1/admin/brew-methods/{$brewMethod->id}", ['name' => 'New Name'])
            ->assertStatus(200)
            ->assertJsonFragment(['name' => 'New Name']);
    }

    public function test_admin_can_delete_brew_method(): void
    {
        $brewMethod = BrewMethod::create([
            'name'     => 'To Delete',
            'slug'     => 'to-delete',
            'category' => BrewMethodCategory::Filter->value,
        ]);

        $this->actingAs($this->admin(), 'sanctum')
            ->deleteJson("/api/v1/admin/brew-methods/{$brewMethod->id}")
            ->assertStatus(204);

        $this->assertDatabaseMissing('brew_methods', ['id' => $brewMethod->id]);
    }

    public function test_regular_user_cannot_create_brew_method(): void
    {
        $this->actingAs($this->user(), 'sanctum')
            ->postJson('/api/v1/admin/brew-methods', ['name' => 'Hack'])
            ->assertStatus(403);
    }

    public function test_regular_user_cannot_update_brew_method(): void
    {
        $brewMethod = BrewMethod::create([
            'name'     => 'V60',
            'slug'     => 'v60',
            'category' => BrewMethodCategory::Filter->value,
        ]);

        $this->actingAs($this->user(), 'sanctum')
            ->putJson("/api/v1/admin/brew-methods/{$brewMethod->id}", ['name' => 'Hack'])
            ->assertStatus(403);
    }

    public function test_regular_user_cannot_delete_brew_method(): void
    {
        $brewMethod = BrewMethod::create([
            'name'     => 'V60',
            'slug'     => 'v60',
            'category' => BrewMethodCategory::Filter->value,
        ]);

        $this->actingAs($this->user(), 'sanctum')
            ->deleteJson("/api/v1/admin/brew-methods/{$brewMethod->id}")
            ->assertStatus(403);
    }

    public function test_unauthenticated_user_cannot_access_admin_routes(): void
    {
        $this->postJson('/api/v1/admin/brew-methods', ['name' => 'Hack'])
            ->assertStatus(401);
    }
}
