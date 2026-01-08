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
        Schema::create('movie_clicks', function (Blueprint $table): void {
            $table->id();
            $table->string('ip_address', 45)->index();
            $table->unsignedBigInteger('tmdb_movie_id')->index();
            $table->string('movie_title');
            $table->string('poster_path')->nullable();
            $table->timestamp('clicked_at');
            $table->timestamps();

            $table->index(['tmdb_movie_id', 'clicked_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('movie_clicks');
    }
};
