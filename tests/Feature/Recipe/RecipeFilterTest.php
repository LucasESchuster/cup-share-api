<?php

namespace Tests\Feature\Recipe;

use App\Models\BrewMethod;
use App\Models\Recipe;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecipeFilterTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private BrewMethod $v60;
    private BrewMethod $espresso;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->v60 = BrewMethod::create(['name' => 'V60', 'slug' => 'v60', 'category' => 'filter']);
        $this->espresso = BrewMethod::create(['name' => 'La Marzocco', 'slug' => 'la-marzocco', 'category' => 'espresso']);
    }

    private function recipeIds(string $url): array
    {
        return $this->getJson($url)->assertStatus(200)->json('data.*.id');
    }

    // --- title ---

    public function test_filter_by_title_returns_matching_recipes(): void
    {
        $match = Recipe::factory()->create(['user_id' => $this->user->id, 'brew_method_id' => $this->v60->id, 'title' => 'Receita de V60 especial', 'visibility' => 'public']);
        $noMatch = Recipe::factory()->create(['user_id' => $this->user->id, 'brew_method_id' => $this->v60->id, 'title' => 'Espresso clássico', 'visibility' => 'public']);

        $ids = $this->recipeIds('/api/v1/recipes?title=v60');

        $this->assertContains($match->id, $ids);
        $this->assertNotContains($noMatch->id, $ids);
    }

    public function test_filter_by_title_is_case_insensitive(): void
    {
        $recipe = Recipe::factory()->create(['user_id' => $this->user->id, 'brew_method_id' => $this->v60->id, 'title' => 'Receita de Verão', 'visibility' => 'public']);

        $this->assertContains($recipe->id, $this->recipeIds('/api/v1/recipes?title=VERÃO'));
    }

    // --- brew_method_id ---

    public function test_filter_by_brew_method_id_returns_matching_recipes(): void
    {
        $v60Recipe = Recipe::factory()->create(['user_id' => $this->user->id, 'brew_method_id' => $this->v60->id, 'visibility' => 'public']);
        $espressoRecipe = Recipe::factory()->create(['user_id' => $this->user->id, 'brew_method_id' => $this->espresso->id, 'visibility' => 'public']);

        $ids = $this->recipeIds("/api/v1/recipes?brew_method_id={$this->v60->id}");

        $this->assertContains($v60Recipe->id, $ids);
        $this->assertNotContains($espressoRecipe->id, $ids);
    }

    public function test_filter_by_nonexistent_brew_method_id_returns_422(): void
    {
        $this->getJson('/api/v1/recipes?brew_method_id=99999')
            ->assertStatus(422);
    }

    // --- category ---

    public function test_filter_by_category_returns_matching_recipes(): void
    {
        $filterRecipe = Recipe::factory()->create(['user_id' => $this->user->id, 'brew_method_id' => $this->v60->id, 'visibility' => 'public']);
        $espressoRecipe = Recipe::factory()->create(['user_id' => $this->user->id, 'brew_method_id' => $this->espresso->id, 'visibility' => 'public']);

        $ids = $this->recipeIds('/api/v1/recipes?category=filter');

        $this->assertContains($filterRecipe->id, $ids);
        $this->assertNotContains($espressoRecipe->id, $ids);
    }

    public function test_filter_by_invalid_category_returns_422(): void
    {
        $this->getJson('/api/v1/recipes?category=invalid')
            ->assertStatus(422);
    }

    // --- user_id ---

    public function test_filter_by_user_id_returns_only_that_users_recipes(): void
    {
        $otherUser = User::factory()->create();
        $mine = Recipe::factory()->create(['user_id' => $this->user->id, 'brew_method_id' => $this->v60->id, 'visibility' => 'public']);
        $theirs = Recipe::factory()->create(['user_id' => $otherUser->id, 'brew_method_id' => $this->v60->id, 'visibility' => 'public']);

        $ids = $this->recipeIds("/api/v1/recipes?user_id={$this->user->id}");

        $this->assertContains($mine->id, $ids);
        $this->assertNotContains($theirs->id, $ids);
    }

    public function test_filter_by_nonexistent_user_id_returns_422(): void
    {
        $this->getJson('/api/v1/recipes?user_id=99999')
            ->assertStatus(422);
    }

    // --- published_from / published_to ---

    public function test_filter_by_published_from_excludes_older_recipes(): void
    {
        $old = Recipe::factory()->create(['user_id' => $this->user->id, 'brew_method_id' => $this->v60->id, 'visibility' => 'public', 'created_at' => '2026-01-10']);
        $new = Recipe::factory()->create(['user_id' => $this->user->id, 'brew_method_id' => $this->v60->id, 'visibility' => 'public', 'created_at' => '2026-03-01']);

        $ids = $this->recipeIds('/api/v1/recipes?published_from=2026-02-01');

        $this->assertContains($new->id, $ids);
        $this->assertNotContains($old->id, $ids);
    }

    public function test_filter_by_published_to_excludes_newer_recipes(): void
    {
        $old = Recipe::factory()->create(['user_id' => $this->user->id, 'brew_method_id' => $this->v60->id, 'visibility' => 'public', 'created_at' => '2026-01-10']);
        $new = Recipe::factory()->create(['user_id' => $this->user->id, 'brew_method_id' => $this->v60->id, 'visibility' => 'public', 'created_at' => '2026-03-01']);

        $ids = $this->recipeIds('/api/v1/recipes?published_to=2026-02-01');

        $this->assertContains($old->id, $ids);
        $this->assertNotContains($new->id, $ids);
    }

    public function test_filter_by_date_range_returns_recipes_within_range(): void
    {
        $before = Recipe::factory()->create(['user_id' => $this->user->id, 'brew_method_id' => $this->v60->id, 'visibility' => 'public', 'created_at' => '2026-01-01']);
        $within = Recipe::factory()->create(['user_id' => $this->user->id, 'brew_method_id' => $this->v60->id, 'visibility' => 'public', 'created_at' => '2026-02-15']);
        $after  = Recipe::factory()->create(['user_id' => $this->user->id, 'brew_method_id' => $this->v60->id, 'visibility' => 'public', 'created_at' => '2026-03-20']);

        $ids = $this->recipeIds('/api/v1/recipes?published_from=2026-02-01&published_to=2026-02-28');

        $this->assertContains($within->id, $ids);
        $this->assertNotContains($before->id, $ids);
        $this->assertNotContains($after->id, $ids);
    }

    public function test_published_to_before_published_from_returns_422(): void
    {
        $this->getJson('/api/v1/recipes?published_from=2026-03-01&published_to=2026-01-01')
            ->assertStatus(422);
    }

    // --- sort_by / sort_dir ---

    public function test_sort_by_likes_count_descending(): void
    {
        $popular = Recipe::factory()->create(['user_id' => $this->user->id, 'brew_method_id' => $this->v60->id, 'visibility' => 'public', 'likes_count' => 50]);
        $unpopular = Recipe::factory()->create(['user_id' => $this->user->id, 'brew_method_id' => $this->v60->id, 'visibility' => 'public', 'likes_count' => 2]);

        $ids = $this->getJson('/api/v1/recipes?sort_by=likes_count&sort_dir=desc')
            ->assertStatus(200)
            ->json('data.*.id');

        $this->assertGreaterThan(array_search($popular->id, $ids), array_search($unpopular->id, $ids));
    }

    public function test_sort_by_likes_count_ascending(): void
    {
        $popular = Recipe::factory()->create(['user_id' => $this->user->id, 'brew_method_id' => $this->v60->id, 'visibility' => 'public', 'likes_count' => 50]);
        $unpopular = Recipe::factory()->create(['user_id' => $this->user->id, 'brew_method_id' => $this->v60->id, 'visibility' => 'public', 'likes_count' => 2]);

        $ids = $this->getJson('/api/v1/recipes?sort_by=likes_count&sort_dir=asc')
            ->assertStatus(200)
            ->json('data.*.id');

        $this->assertGreaterThan(array_search($unpopular->id, $ids), array_search($popular->id, $ids));
    }

    public function test_sort_by_created_at_ascending(): void
    {
        $old = Recipe::factory()->create(['user_id' => $this->user->id, 'brew_method_id' => $this->v60->id, 'visibility' => 'public', 'created_at' => '2026-01-01']);
        $new = Recipe::factory()->create(['user_id' => $this->user->id, 'brew_method_id' => $this->v60->id, 'visibility' => 'public', 'created_at' => '2026-03-01']);

        $ids = $this->getJson('/api/v1/recipes?sort_by=created_at&sort_dir=asc')
            ->assertStatus(200)
            ->json('data.*.id');

        $this->assertGreaterThan(array_search($old->id, $ids), array_search($new->id, $ids));
    }

    public function test_invalid_sort_by_returns_422(): void
    {
        $this->getJson('/api/v1/recipes?sort_by=invalid_column')
            ->assertStatus(422);
    }

    public function test_invalid_sort_dir_returns_422(): void
    {
        $this->getJson('/api/v1/recipes?sort_dir=sideways')
            ->assertStatus(422);
    }
}
