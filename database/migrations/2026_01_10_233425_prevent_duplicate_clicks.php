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
        DB::transaction(function (): void {
            $subquery = DB::table('movie_clicks as mc1')
                ->select('mc1.id')
                ->join('movie_clicks as mc2', function ($join) {
                    $join->on('mc1.ip_address', '=', 'mc2.ip_address')
                        ->on('mc1.tmdb_movie_id', '=', 'mc2.tmdb_movie_id')
                        ->on('mc1.clicked_at', '=', 'mc2.clicked_at');
                })
                ->where('mc1.id', '<', 'mc2.id');

            DB::table('movie_clicks')
                ->whereIn('id', $subquery)
                ->delete();

            Schema::table('movie_clicks', function (Blueprint $table): void {
                $table->unique(['ip_address', 'tmdb_movie_id', 'clicked_at'], 'unique_click_per_ip_movie_second');
            });
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('movie_clicks', function (Blueprint $table): void {
            $table->dropUnique('unique_click_per_ip_movie_second');
        });
    }
};
