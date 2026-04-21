<?php

namespace Tests\Feature\E2E;

use App\Models\BrewMethod;
use App\Models\MagicLink;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OnboardingFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_user_onboards_via_magic_link_and_creates_first_recipe(): void
    {
        $brewMethod = BrewMethod::create(['name' => 'V60', 'slug' => 'v60', 'category' => 'filter']);
        $email = 'onboard@example.com';

        $this->postJson('/api/v1/auth/magic-link', ['email' => $email])
            ->assertStatus(202)
            ->assertJsonStructure(['message']);

        $this->assertDatabaseHas('users', ['email' => $email]);

        $token = MagicLink::latest('id')->value('token');
        $this->assertNotNull($token);
        $this->assertEquals(64, strlen($token));

        $bearer = $this->getJson("/api/v1/auth/magic-link/{$token}")
            ->assertStatus(200)
            ->assertJsonStructure(['token'])
            ->json('token');

        $this->assertNotEmpty($bearer);

        $this->withToken($bearer)
            ->getJson('/api/v1/users/me')
            ->assertStatus(200)
            ->assertJsonFragment(['email' => $email])
            ->assertJsonStructure(['data' => ['id', 'name', 'email', 'is_admin', 'banned_at', 'created_at']]);

        $this->withToken($bearer)
            ->putJson('/api/v1/users/me', ['name' => 'Maria Onboarded'])
            ->assertStatus(200)
            ->assertJsonFragment(['name' => 'Maria Onboarded']);

        $recipeResponse = $this->withToken($bearer)
            ->postJson('/api/v1/recipes', [
                'title' => 'Primeira receita onboarding',
                'brew_method_id' => $brewMethod->id,
                'coffee_grams' => 15,
                'water_ml' => 250,
                'brew_time_seconds' => 210,
                'steps' => [
                    ['order' => 1, 'description' => 'Aquecer a água a 95°C'],
                    ['order' => 2, 'description' => 'Despejar em movimentos circulares'],
                ],
            ])
            ->assertStatus(201)
            ->assertJsonFragment(['title' => 'Primeira receita onboarding'])
            ->assertJsonFragment(['visibility' => 'public']);

        $recipeId = $recipeResponse->json('data.id');
        $this->assertNotNull($recipeId);

        $this->getJson('/api/v1/recipes')
            ->assertStatus(200)
            ->assertJsonFragment(['id' => $recipeId, 'title' => 'Primeira receita onboarding']);

        $this->assertDatabaseHas('recipes', ['id' => $recipeId, 'title' => 'Primeira receita onboarding']);
        $this->assertDatabaseCount('recipe_steps', 2);
    }

    public function test_onboarding_marks_email_as_verified_on_first_token_consumption(): void
    {
        $email = 'verify@example.com';

        $this->postJson('/api/v1/auth/magic-link', ['email' => $email])
            ->assertStatus(202);

        $this->assertDatabaseHas('users', ['email' => $email, 'email_verified_at' => null]);

        $token = MagicLink::latest('id')->value('token');

        $this->getJson("/api/v1/auth/magic-link/{$token}")
            ->assertStatus(200);

        $this->assertNotNull(\App\Models\User::where('email', $email)->value('email_verified_at'));
    }
}
