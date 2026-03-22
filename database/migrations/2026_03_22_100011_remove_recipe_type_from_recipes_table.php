<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recipes', function (Blueprint $table) {
            $table->dropForeign(['recipe_type_id']);
            $table->dropIndex(['recipe_type_id']);
            $table->dropColumn('recipe_type_id');
        });

        Schema::dropIfExists('recipe_types');
    }

    public function down(): void
    {
        Schema::create('recipe_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->timestamps();
        });

        Schema::table('recipes', function (Blueprint $table) {
            $table->foreignId('recipe_type_id')->nullable()->constrained()->restrictOnDelete();
            $table->index('recipe_type_id');
        });
    }
};
