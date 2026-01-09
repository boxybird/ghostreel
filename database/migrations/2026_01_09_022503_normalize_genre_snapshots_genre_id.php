<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Truncate existing data (testing phase - no need to preserve)
        DB::table('genre_snapshots')->truncate();

        // Drop old indexes that reference genre_id
        Schema::table('genre_snapshots', function (Blueprint $table): void {
            $table->dropUnique(['movie_id', 'genre_id', 'snapshot_date']);
            $table->dropIndex(['genre_id', 'snapshot_date', 'position']);
        });

        // Drop the old genre_id column (currently stores tmdb_id)
        Schema::table('genre_snapshots', function (Blueprint $table): void {
            $table->dropColumn('genre_id');
        });

        // Add proper foreign key column
        Schema::table('genre_snapshots', function (Blueprint $table): void {
            $table->foreignId('genre_id')->nullable()->after('movie_id')->constrained()->cascadeOnDelete();

            // Re-add indexes with proper FK
            $table->unique(['movie_id', 'genre_id', 'snapshot_date']);
            $table->index(['genre_id', 'snapshot_date', 'position']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop new indexes
        Schema::table('genre_snapshots', function (Blueprint $table): void {
            $table->dropUnique(['movie_id', 'genre_id', 'snapshot_date']);
            $table->dropIndex(['genre_id', 'snapshot_date', 'position']);
            $table->dropForeign(['genre_id']);
            $table->dropColumn('genre_id');
        });

        // Restore old column structure
        Schema::table('genre_snapshots', function (Blueprint $table): void {
            $table->unsignedInteger('genre_id')->after('movie_id');
            $table->unique(['movie_id', 'genre_id', 'snapshot_date']);
            $table->index(['genre_id', 'snapshot_date', 'position']);
        });
    }
};
