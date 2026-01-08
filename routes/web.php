<?php

use App\Http\Controllers\GenreController;
use App\Http\Controllers\HeatmapController;
use App\Http\Controllers\MovieController;
use App\Http\Controllers\PopularController;
use App\Http\Controllers\SearchController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HeatmapController::class, 'index'])->name('heatmap.index');
Route::get('/trending', [HeatmapController::class, 'trending'])->name('heatmap.trending');
Route::post('/click', [HeatmapController::class, 'logClick'])->name('heatmap.click');
Route::get('/recent-views', [HeatmapController::class, 'recentViews'])->name('heatmap.recent');
Route::get('/heatmap-data', [HeatmapController::class, 'heatmapData'])->name('heatmap.data');

Route::get('/popular', [PopularController::class, 'index'])->name('popular.index');

Route::get('/search', [SearchController::class, 'search'])->name('search');

Route::get('/genres', [GenreController::class, 'index'])->name('genres.index');
Route::get('/genres/{genreId}', [GenreController::class, 'show'])->name('genres.show')->whereNumber('genreId');

Route::get('/movies/{movie}', [MovieController::class, 'show'])->name('movies.show');
