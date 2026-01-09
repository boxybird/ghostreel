<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('movies', function (Blueprint $table): void {
            $table->decimal('tmdb_popularity', 10, 3)->nullable()->after('vote_average');
            $table->index('tmdb_popularity');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('movies', function (Blueprint $table): void {
            $table->dropIndex(['tmdb_popularity']);
            $table->dropColumn('tmdb_popularity');
        });
    }
};
