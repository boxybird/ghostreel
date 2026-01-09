<?php

namespace App\Providers;

use App\Services\MovieService;
use App\Services\TmdbService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(TmdbService::class, function (): TmdbService {
            return new TmdbService(
                apiToken: config('services.tmdb.api_key'),
            );
        });

        $this->app->singleton(MovieService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Model::shouldBeStrict();
    }
}
