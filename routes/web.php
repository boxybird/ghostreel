<?php

use App\Http\Controllers\HeatmapController;
use App\Http\Controllers\PopularController;
use App\Http\Controllers\SearchController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HeatmapController::class, 'index'])->name('heatmap.index');
Route::post('/click', [HeatmapController::class, 'logClick'])->name('heatmap.click');
Route::get('/recent-views', [HeatmapController::class, 'recentViews'])->name('heatmap.recent');
Route::get('/heatmap-data', [HeatmapController::class, 'heatmapData'])->name('heatmap.data');

Route::get('/popular', [PopularController::class, 'index'])->name('popular.index');

Route::get('/search', [SearchController::class, 'search'])->name('search');
