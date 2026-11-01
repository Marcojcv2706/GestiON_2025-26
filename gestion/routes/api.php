<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CalendarioController;
use App\Http\Controllers\Api\ReporteRecursoController;

Route::get('/calendario', [CalendarioController::class, 'index'])
    ->name('api.calendario.index');

Route::middleware(['web', 'auth', 'can:es-admin'])
    ->get('/reportes/recursos', ReporteRecursoController::class)
    ->name('api.reportes.recursos');