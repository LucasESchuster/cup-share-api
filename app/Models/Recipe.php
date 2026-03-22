<?php

namespace App\Models;

use App\Enums\RecipeVisibility;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Recipe extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'brew_method_id',
        'recipe_type_id',
        'title',
        'slug',
        'description',
        'coffee_grams',
        'water_ml',
        'yield_ml',
        'brew_time_seconds',
        'visibility',
        'video_url',
        'water_temperature_celsius',
        'coffee_description',
    ];

    protected function casts(): array
    {
        return [
            'coffee_grams' => 'decimal:1',
            'visibility' => RecipeVisibility::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function brewMethod(): BelongsTo
    {
        return $this->belongsTo(BrewMethod::class);
    }

    public function recipeType(): BelongsTo
    {
        return $this->belongsTo(RecipeType::class);
    }

    public function steps(): HasMany
    {
        return $this->hasMany(RecipeStep::class)->orderBy('order');
    }

    public function ingredients(): BelongsToMany
    {
        return $this->belongsToMany(Ingredient::class, 'recipe_ingredient')
            ->withPivot('quantity', 'unit');
    }

    public function equipment(): BelongsToMany
    {
        return $this->belongsToMany(Equipment::class, 'recipe_equipment')
            ->withPivot('grinder_clicks', 'parameters');
    }

    public function likes(): HasMany
    {
        return $this->hasMany(Like::class);
    }

    public function scopePublic(Builder $query): Builder
    {
        return $query->where('visibility', RecipeVisibility::Public->value);
    }

    public function scopeForUser(Builder $query, ?User $user): Builder
    {
        if (! $user) {
            return $query->where('visibility', RecipeVisibility::Public->value);
        }

        return $query->where(fn (Builder $q) => $q
            ->where('visibility', RecipeVisibility::Public->value)
            ->orWhere(fn (Builder $q2) => $q2
                ->where('visibility', RecipeVisibility::Private->value)
                ->where('user_id', $user->id)
            )
        );
    }
}
