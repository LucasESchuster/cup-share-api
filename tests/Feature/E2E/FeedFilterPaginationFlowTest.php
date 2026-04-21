<?php

namespace Tests\Feature\E2E;

use App\Models\BrewMethod;
use App\Models\Recipe;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeedFilterPaginationFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_feed_is_paginated_with_15_per_page(): void
    {
        $brewMethod = BrewMethod::create(['name' => 'V60', 'slug' => 'v60', 'category' => 'filter']);
        $author = User::factory()->create();

        Recipe::factory(20)->create([
            'user_id' => $author->id,
            'brew_method_id' => $brewMethod->id,
            'visibility' => 'public',
        ]);

        $first = $this->getJson('/api/v1/recipes')
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [['id', 'title']],
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
                'links',
            ]);

        $this->assertEquals(15, $first->json('meta.per_page'));
        $this->assertEquals(1, $first->json('meta.current_page'));
        $this->assertEquals(20, $first->json('meta.total'));
        $this->assertEquals(2, $first->json('meta.last_page'));
        $this->assertCount(15, $first->json('data'));

        $second = $this->getJson('/api/v1/recipes?page=2')
            ->assertStatus(200);

        $this->assertCount(5, $second->json('data'));
        $this->assertEquals(2, $second->json('meta.current_page'));
    }

    public function test_feed_filters_by_brew_method_id(): void
    {
        $v60 = BrewMethod::create(['name' => 'V60', 'slug' => 'v60', 'category' => 'filter']);
        $espresso = BrewMethod::create(['name' => 'Espresso', 'slug' => 'espresso', 'category' => 'espresso']);
        $author = User::factory()->create();

        Recipe::factory(3)->create([
            'user_id' => $author->id,
            'brew_method_id' => $v60->id,
            'visibility' => 'public',
        ]);

        Recipe::factory(2)->create([
            'user_id' => $author->id,
            'brew_method_id' => $espresso->id,
            'visibility' => 'public',
        ]);

        $response = $this->getJson("/api/v1/recipes?brew_method_id={$v60->id}")
            ->assertStatus(200);

        $this->assertEquals(3, $response->json('meta.total'));

        $response = $this->getJson("/api/v1/recipes?brew_method_id={$espresso->id}")
            ->assertStatus(200);

        $this->assertEquals(2, $response->json('meta.total'));
    }

    public function test_feed_filters_by_category(): void
    {
        $v60 = BrewMethod::create(['name' => 'V60', 'slug' => 'v60', 'category' => 'filter']);
        $aero = BrewMethod::create(['name' => 'Aeropress', 'slug' => 'aeropress', 'category' => 'filter']);
        $espresso = BrewMethod::create(['name' => 'Espresso', 'slug' => 'espresso', 'category' => 'espresso']);
        $author = User::factory()->create();

        Recipe::factory(2)->create(['user_id' => $author->id, 'brew_method_id' => $v60->id, 'visibility' => 'public']);
        Recipe::factory(2)->create(['user_id' => $author->id, 'brew_method_id' => $aero->id, 'visibility' => 'public']);
        Recipe::factory(1)->create(['user_id' => $author->id, 'brew_method_id' => $espresso->id, 'visibility' => 'public']);

        $response = $this->getJson('/api/v1/recipes?category=filter')
            ->assertStatus(200);

        $this->assertEquals(4, $response->json('meta.total'));
    }

    public function test_feed_hides_private_recipes_from_anonymous_and_from_other_users(): void
    {
        $brewMethod = BrewMethod::create(['name' => 'V60', 'slug' => 'v60', 'category' => 'filter']);
        $author = User::factory()->create();
        $other = User::factory()->create();

        Recipe::factory()->create([
            'user_id' => $author->id,
            'brew_method_id' => $brewMethod->id,
            'visibility' => 'public',
            'title' => 'Pública',
        ]);

        $private = Recipe::factory()->create([
            'user_id' => $author->id,
            'brew_method_id' => $brewMethod->id,
            'visibility' => 'private',
            'title' => 'Privada',
        ]);

        $this->getJson('/api/v1/recipes')
            ->assertStatus(200)
            ->assertJsonFragment(['title' => 'Pública'])
            ->assertJsonMissing(['title' => 'Privada']);

        $this->actingAs($other, 'sanctum')
            ->getJson('/api/v1/recipes')
            ->assertJsonFragment(['title' => 'Pública'])
            ->assertJsonMissing(['title' => 'Privada']);

        $this->actingAs($other, 'sanctum')
            ->getJson("/api/v1/recipes/{$private->id}")
            ->assertStatus(404);
    }

    public function test_my_recipes_endpoint_returns_private_and_public_for_owner(): void
    {
        $brewMethod = BrewMethod::create(['name' => 'V60', 'slug' => 'v60', 'category' => 'filter']);
        $author = User::factory()->create();

        Recipe::factory(2)->create([
            'user_id' => $author->id,
            'brew_method_id' => $brewMethod->id,
            'visibility' => 'public',
        ]);

        Recipe::factory(3)->create([
            'user_id' => $author->id,
            'brew_method_id' => $brewMethod->id,
            'visibility' => 'private',
        ]);

        $response = $this->actingAs($author, 'sanctum')
            ->getJson('/api/v1/users/me/recipes')
            ->assertStatus(200);

        $this->assertEquals(5, $response->json('meta.total'));

        $this->actingAs($author, 'sanctum')
            ->getJson('/api/v1/recipes')
            ->assertStatus(200)
            ->assertJsonPath('meta.total', 2);
    }

    public function test_feed_sorts_by_likes_count_desc(): void
    {
        $brewMethod = BrewMethod::create(['name' => 'V60', 'slug' => 'v60', 'category' => 'filter']);
        $author = User::factory()->create();

        $low = Recipe::factory()->create([
            'user_id' => $author->id,
            'brew_method_id' => $brewMethod->id,
            'visibility' => 'public',
            'likes_count' => 2,
        ]);

        $high = Recipe::factory()->create([
            'user_id' => $author->id,
            'brew_method_id' => $brewMethod->id,
            'visibility' => 'public',
            'likes_count' => 10,
        ]);

        $response = $this->getJson('/api/v1/recipes?sort_by=likes_count&sort_dir=desc')
            ->assertStatus(200);

        $ids = collect($response->json('data'))->pluck('id')->all();
        $this->assertEquals($high->id, $ids[0]);
        $this->assertEquals($low->id, $ids[1]);
    }
}
