<?php

namespace App\Models;

use App\Enums\EquipmentType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Equipment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'brand',
        'model',
        'type',
    ];

    protected function casts(): array
    {
        return [
            'type' => EquipmentType::class,
        ];
    }

    public function recipes(): BelongsToMany
    {
        return $this->belongsToMany(Recipe::class, 'recipe_equipment')
            ->withPivot('grinder_clicks', 'parameters');
    }

    public function scopeGrinders(Builder $query): Builder
    {
        return $query->where('type', EquipmentType::Grinder->value);
    }
}
