<?php

use Illuminate\Support\Facades\Route;

Route::get('/', [\App\Http\Controllers\StatsController::class, 'home'])->name("cmusic.home");