<?php

use Illuminate\Support\Facades\Route;

Route::get('/', [\App\Http\Controllers\StatsController::class, 'home'])->name("cmusic.home");
Route::get('/songs', [\App\Http\Controllers\AudioController::class, 'home'])->name("cmusic.songs");
Route::get('/jobs', [\App\Http\Controllers\JobsController::class, 'home'])->name("cmusic.jobs");
Route::get('/queue', [\App\Http\Controllers\AudioController::class, 'queue'])->name("cmusic.queue");
Route::get('/cache_miss', [\App\Http\Controllers\JobsController::class, 'forceMiss'])->name("cmusic.forceMiss");
Route::prefix('/jobs')->group(function() {
    Route::get('/force_all', [\App\Http\Controllers\JobsController::class, 'processAll'])->name("cmusic.jobs.processAll");
});

Route::prefix('/meta')->group(function() {
    Route::get('/cover/{id}', [\App\Http\Controllers\AudioController::class, 'albumCover'])->name("cmusic.meta.cover");
    Route::get('/file/{id}', [\App\Http\Controllers\AudioController::class, 'file'])->name("cmusic.meta.file");
    Route::get('/json/{id}', [\App\Http\Controllers\AudioController::class, 'json'])->name("cmusic.meta.json");
});

Route::prefix('/debug')->group(function() {
    Route::get('/test_audio', [\App\Http\Controllers\DebugController::class, 'testAudio'])->name("cmusic.jobs.processAll");
});