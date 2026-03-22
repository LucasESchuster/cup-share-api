<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('brew_methods', function (Blueprint $table) {
            $table->string('category')->default('filter')->after('description');
        });

        DB::table('brew_methods')->where('slug', 'espresso')->update(['category' => 'espresso']);
        DB::table('brew_methods')->where('slug', 'cold-brew')->update(['category' => 'cold_brew']);
        DB::table('brew_methods')->where('slug', 'moka-pot')->update(['category' => 'pressure']);
    }

    public function down(): void
    {
        Schema::table('brew_methods', function (Blueprint $table) {
            $table->dropColumn('category');
        });
    }
};
