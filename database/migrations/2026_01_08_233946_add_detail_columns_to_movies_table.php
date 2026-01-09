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
            $table->string('tagline')->nullable()->after('tmdb_popularity');
            $table->unsignedSmallInteger('runtime')->nullable()->after('tagline');
            $table->json('crew')->nullable()->after('runtime');
            $table->json('similar_tmdb_ids')->nullable()->after('crew');
            $table->json('genre_ids')->nullable()->after('similar_tmdb_ids');
            $table->timestamp('details_synced_at')->nullable()->after('genre_ids');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('movies', function (Blueprint $table): void {
            $table->dropColumn([
                'tagline',
                'runtime',
                'crew',
                'similar_tmdb_ids',
                'genre_ids',
                'details_synced_at',
            ]);
        });
    }
};
