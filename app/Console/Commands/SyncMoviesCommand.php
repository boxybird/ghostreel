<?php

namespace App\Console\Commands;

use App\Jobs\SyncAllGenresJob;
use App\Jobs\SyncTrendingMoviesJob;
use Illuminate\Console\Command;

class SyncMoviesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'movies:sync
                            {--trending : Sync trending movies}
                            {--genres : Sync all genres and their movies}
                            {--all : Sync everything (trending + genres)}
                            {--pages=5 : Number of pages to sync per category}
                            {--sync : Run synchronously instead of dispatching to queue}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync movies from TMDB to local database';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $pages = (int) $this->option('pages');
        $syncAll = $this->option('all');
        $syncTrending = $this->option('trending') || $syncAll;
        $syncGenres = $this->option('genres') || $syncAll;
        $runSync = $this->option('sync');

        if (! $syncTrending && ! $syncGenres) {
            $this->error('Please specify at least one option: --trending, --genres, or --all');

            return self::FAILURE;
        }

        $this->info("Starting movie sync (pages: {$pages})...");

        if ($syncTrending) {
            $this->syncTrending($pages, $runSync);
        }

        if ($syncGenres) {
            $this->syncGenres($pages, $runSync);
        }

        $this->info('Movie sync initiated successfully!');

        if (! $runSync) {
            $this->info('Jobs have been dispatched to the queue. Run `php artisan queue:work` to process them.');
        }

        return self::SUCCESS;
    }

    /**
     * Sync trending movies.
     */
    private function syncTrending(int $pages, bool $runSync): void
    {
        $this->info('Syncing trending movies...');

        $job = new SyncTrendingMoviesJob(pages: $pages);

        if ($runSync) {
            $this->info('Running synchronously...');
            dispatch_sync($job);
            $this->info('Trending movies synced!');
        } else {
            SyncTrendingMoviesJob::dispatch(pages: $pages);
            $this->info('Trending sync job dispatched.');
        }
    }

    /**
     * Sync all genres and their movies.
     */
    private function syncGenres(int $pages, bool $runSync): void
    {
        $this->info('Syncing all genres and movies...');

        $job = new SyncAllGenresJob(pagesPerGenre: $pages, syncMovies: true);

        if ($runSync) {
            $this->info('Running synchronously...');
            dispatch_sync($job);
            $this->info('Genres synced! Note: Genre movie jobs were dispatched to queue.');
        } else {
            SyncAllGenresJob::dispatch(pagesPerGenre: $pages, syncMovies: true);
            $this->info('Genre sync job dispatched.');
        }
    }
}
