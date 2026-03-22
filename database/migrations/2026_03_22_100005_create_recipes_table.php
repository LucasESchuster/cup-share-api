<?php

use App\Enums\RecipeVisibility;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recipes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('brew_method_id')->constrained()->restrictOnDelete();
            $table->foreignId('recipe_type_id')->constrained()->restrictOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->decimal('coffee_grams', 6, 1);
            $table->integer('water_ml')->nullable();
            $table->integer('yield_ml')->nullable();
            $table->integer('brew_time_seconds');
            $table->string('visibility')->default(RecipeVisibility::Public->value);
            $table->unsignedInteger('likes_count')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index('user_id');
            $table->index('brew_method_id');
            $table->index('recipe_type_id');
            $table->index(['visibility', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recipes');
    }
};
