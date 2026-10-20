<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CalendarioController;

Route::get('/calendario', [CalendarioController::class, 'index'])
    ->name('api.calendario.index');
