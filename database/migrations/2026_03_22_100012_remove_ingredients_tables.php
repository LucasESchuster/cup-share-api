<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('recipe_ingredient');
        Schema::dropIfExists('ingredients');
    }

    public function down(): void
    {
        Schema::create('ingredients', function ($table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->timestamps();
        });

        Schema::create('recipe_ingredient', function ($table) {
            $table->id();
            $table->foreignId('recipe_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ingredient_id')->constrained()->cascadeOnDelete();
            $table->decimal('quantity', 8, 2);
            $table->string('unit', 50);
            $table->unique(['recipe_id', 'ingredient_id']);
        });
    }
};
