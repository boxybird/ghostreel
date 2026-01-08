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
        Schema::create('movies', function (Blueprint $table): void {
            $table->id();
            $table->unsignedInteger('tmdb_id')->unique();
            $table->string('title')->index();
            $table->string('poster_path')->nullable();
            $table->string('backdrop_path')->nullable();
            $table->text('overview')->nullable();
            $table->date('release_date')->nullable();
            $table->decimal('vote_average', 3, 1)->default(0);
            $table->enum('source', ['trending', 'search'])->default('search');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('movies');
    }
};
