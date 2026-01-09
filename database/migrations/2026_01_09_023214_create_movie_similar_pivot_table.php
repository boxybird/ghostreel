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
        // Create pivot table for self-referential many-to-many relationship
        Schema::create('movie_similar', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('movie_id')->constrained()->cascadeOnDelete();
            $table->foreignId('similar_movie_id')->constrained('movies')->cascadeOnDelete();
            $table->timestamps();

            // Prevent duplicate similar movie entries
            $table->unique(['movie_id', 'similar_movie_id']);
        });

        // Drop the old JSON column (no data preservation needed)
        Schema::table('movies', function (Blueprint $table): void {
            $table->dropColumn('similar_tmdb_ids');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Restore the old JSON column
        Schema::table('movies', function (Blueprint $table): void {
            $table->json('similar_tmdb_ids')->nullable()->after('crew');
        });

        // Drop the pivot table
        Schema::dropIfExists('movie_similar');
    }
};
