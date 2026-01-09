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
        Schema::create('trending_snapshots', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('movie_id')->constrained()->cascadeOnDelete();
            $table->string('list_type')->default('trending_day');
            $table->unsignedSmallInteger('position');
            $table->unsignedSmallInteger('page');
            $table->date('snapshot_date')->index();
            $table->timestamps();

            $table->unique(['movie_id', 'list_type', 'snapshot_date']);
            $table->index(['list_type', 'snapshot_date', 'position']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trending_snapshots');
    }
};
