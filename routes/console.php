<?php

use App\Jobs\SyncAllGenresJob;
use App\Jobs\SyncTrendingMoviesJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function (): void {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Movie Sync Schedules
|--------------------------------------------------------------------------
|
| These schedules run nightly to sync movie data from TMDB to the local
| database, ensuring fresh trending and genre data is always available.
|
*/

// Sync trending movies at 3:00 AM daily
Schedule::job(new SyncTrendingMoviesJob(pages: 5))->dailyAt('03:00');

// Sync all genres and their movies at 3:15 AM daily
Schedule::job(new SyncAllGenresJob(pagesPerGenre: 5, syncMovies: true))->dailyAt('03:15');
