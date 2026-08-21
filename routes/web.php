<?php

use Illuminate\Support\Facades\Route;

Route::get('/', [\App\Http\Controllers\StatsController::class, 'home'])->name("cmusic.home");
Route::get('/jobs', [\App\Http\Controllers\JobsController::class, 'home'])->name("cmusic.jobs");
Route::get('/cache_miss', [\App\Http\Controllers\JobsController::class, 'forceMiss'])->name("cmusic.forceMiss");
Route::prefix('/jobs')->group(function() {
    Route::get('/force_all', [\App\Http\Controllers\JobsController::class, 'processAll'])->name("cmusic.jobs.processAll");
});

Route::prefix('/debug')->group(function() {
    Route::get('/test_audio', [\App\Http\Controllers\DebugController::class, 'testAudio'])->name("cmusic.jobs.processAll");
});