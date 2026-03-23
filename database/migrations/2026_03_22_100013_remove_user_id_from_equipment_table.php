<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('equipment', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'type']);
            $table->dropConstrainedForeignId('user_id');
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::table('equipment', function (Blueprint $table) {
            $table->dropIndex(['type']);
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->index(['user_id', 'type']);
        });
    }
};
