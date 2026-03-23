<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecipeEquipment extends Model
{
    public $timestamps = false;

    protected $table = 'recipe_equipment';

    protected $fillable = [
        'recipe_id',
        'equipment_id',
        'custom_name',
        'grinder_clicks',
        'parameters',
    ];

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class);
    }
}
