<?php

use App\Http\Controllers\GenreMoviesController;
use App\Http\Controllers\GenresController;
use App\Http\Controllers\HeatmapController;
use App\Http\Controllers\HeatmapDataController;
use App\Http\Controllers\MovieClickController;
use App\Http\Controllers\MoviesController;
use App\Http\Controllers\PopularMoviesController;
use App\Http\Controllers\RecentClicksController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\TrendingMoviesController;
use Illuminate\Support\Facades\Route;

// Heatmap Dashboard
Route::get('/', [HeatmapController::class, 'index'])->name('heatmap.index');

// Heatmap Data & Interactions
Route::get('/heatmap-data', [HeatmapDataController::class, 'index'])->name('heatmap.data');
Route::post('/clicks', [MovieClickController::class, 'store'])->name('clicks.store');
Route::get('/recent-clicks', [RecentClicksController::class, 'index'])->name('clicks.recent');

// Trending & Popular
Route::get('/trending', [TrendingMoviesController::class, 'index'])->name('trending.index');
Route::get('/popular', [PopularMoviesController::class, 'index'])->name('popular.index');

// Genres
Route::get('/genres', [GenresController::class, 'index'])->name('genres.index');
Route::get('/genres/{genreId}/movies', [GenreMoviesController::class, 'index'])->name('genres.movies.index')->whereNumber('genreId');

// Search
Route::get('/search', [SearchController::class, 'index'])->name('search.index');

// Movie Details
Route::get('/movies/{movie}', [MoviesController::class, 'show'])->name('movies.show');
