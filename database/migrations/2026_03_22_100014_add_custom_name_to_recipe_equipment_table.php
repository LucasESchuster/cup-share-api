<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recipe_equipment', function (Blueprint $table) {
            $table->dropUnique(['recipe_id', 'equipment_id']);
            $table->foreignId('equipment_id')->nullable()->change();
            $table->string('custom_name', 150)->nullable()->after('equipment_id');
        });

        DB::statement('ALTER TABLE recipe_equipment ADD CONSTRAINT chk_equipment_or_custom CHECK (equipment_id IS NOT NULL OR custom_name IS NOT NULL)');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE recipe_equipment DROP CONSTRAINT chk_equipment_or_custom');

        Schema::table('recipe_equipment', function (Blueprint $table) {
            $table->dropColumn('custom_name');
            $table->foreignId('equipment_id')->nullable(false)->change();
            $table->unique(['recipe_id', 'equipment_id']);
        });
    }
};
