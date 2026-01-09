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
        Schema::create('genre_snapshots', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('movie_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('genre_id');
            $table->unsignedSmallInteger('position');
            $table->unsignedSmallInteger('page');
            $table->date('snapshot_date')->index();
            $table->timestamps();

            $table->unique(['movie_id', 'genre_id', 'snapshot_date']);
            $table->index(['genre_id', 'snapshot_date', 'position']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('genre_snapshots');
    }
};
