<?php

use Illuminate\Support\Facades\Route;

Route::get('/', [\App\Http\Controllers\StatsController::class, 'home'])->name("cmusic.home");
Route::get('/jobs', [\App\Http\Controllers\JobsController::class, 'home'])->name("cmusic.jobs");