<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HistorialController;
use App\Http\Controllers\TurnoController;
use App\Http\Controllers\EspacioController;
use App\Http\Controllers\RecursoController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\SubEspacioController;
use App\Http\Controllers\EventoController;
use App\Http\Controllers\SolicitudEventoController;
use App\Http\Controllers\SolicitudRecursoController;
use App\Http\Controllers\ProfileController;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['auth'])->group(function () {

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');

    Route::get('/eventos', [EventoController::class, 'index'])->name('eventos.index');
    Route::post('/eventos', function () {
        abort(403, 'Los eventos deben crearse mediante una solicitud y aprobación administrativa.');
    })->name('eventos.store');

    Route::get('/solicitudes', [SolicitudEventoController::class, 'index'])->name('solicitudes.index');
    Route::get('/solicitudes/crear', [SolicitudEventoController::class, 'create'])
        ->middleware('can:es-profesor')
        ->name('solicitudes.create');
    Route::post('/solicitudes', [SolicitudEventoController::class, 'store'])
        ->middleware('can:es-profesor')
        ->name('solicitudes.store');
    Route::get('/solicitudes/{solicitud}', [SolicitudEventoController::class, 'show'])->name('solicitudes.show');
    Route::get('/solicitudes/{solicitud}/editar', [SolicitudEventoController::class, 'edit'])
        ->middleware('can:es-profesor')
        ->name('solicitudes.edit');
    Route::put('/solicitudes/{solicitud}', [SolicitudEventoController::class, 'update'])
        ->middleware('can:es-profesor')
        ->name('solicitudes.update');
    Route::post('/solicitudes/{solicitud}/reenviar', [SolicitudEventoController::class, 'reenviar'])
        ->middleware('can:es-profesor')
        ->name('solicitudes.reenviar');

    Route::get('/solicitudes-implementos', [SolicitudRecursoController::class, 'index'])
        ->name('solicitudes-recursos.index');
    Route::middleware('can:es-usuario')->group(function () {
        Route::get('/solicitudes-implementos/crear', [SolicitudRecursoController::class, 'create'])
            ->name('solicitudes-recursos.create');
        Route::post('/solicitudes-implementos', [SolicitudRecursoController::class, 'store'])
            ->name('solicitudes-recursos.store');
    });

    Route::middleware(['can:es-admin'])->prefix('admin')->name('admin.')->group(function () {
        Route::post('/solicitudes-implementos/{solicitud}/aprobar', [SolicitudRecursoController::class, 'aprobar'])
            ->name('solicitudes-recursos.aprobar');
        Route::post('/solicitudes-implementos/{solicitud}/rechazar', [SolicitudRecursoController::class, 'rechazar'])
            ->name('solicitudes-recursos.rechazar');
        Route::post('/solicitudes-implementos/{solicitud}/devolver', [SolicitudRecursoController::class, 'devolver'])
            ->name('solicitudes-recursos.devolver');

        Route::post('/solicitudes/{solicitud}/aprobar', [SolicitudEventoController::class, 'aprobar'])
            ->name('solicitudes.aprobar');
        Route::post('/solicitudes/{solicitud}/rechazar', [SolicitudEventoController::class, 'rechazar'])
            ->name('solicitudes.rechazar');
        Route::post('/solicitudes/{solicitud}/solicitar-modificacion', [SolicitudEventoController::class, 'solicitarModificacion'])
            ->name('solicitudes.solicitar-modificacion');
        Route::put('/solicitudes/{solicitud}/modificar', [SolicitudEventoController::class, 'modificarComoAdmin'])
            ->name('solicitudes.modificar');

        Route::post('eventos/{evento}/finalizar', [EventoController::class, 'finalizar'])
            ->name('eventos.finalizar');
        Route::resource('eventos', EventoController::class)->except(['index']);
    });

    Route::get('/historial', [HistorialController::class, 'index'])
        ->name('historial.index');

    Route::get('/calendario', function () {
        return view('calendario.index');
    })->name('calendario.index');

    Route::middleware(['can:es-admin'])
        ->prefix('admin')
        ->name('admin.')
        ->group(function () {

            Route::get('/panel', function () {
                return "Bienvenido al Panel de Administrador";
            })->name('panel');

            Route::resource('espacios', EspacioController::class);
            Route::get('/recursos/plantilla', [RecursoController::class, 'downloadTemplate'])
                ->name('recursos.plantilla');
            Route::get('/recursos/importar', [RecursoController::class, 'importForm'])
                ->name('recursos.importar');
            Route::post('/recursos/importar/previsualizar', [RecursoController::class, 'previewImport'])
                ->name('recursos.importar.previsualizar');
            Route::post('/recursos/importar/confirmar', [RecursoController::class, 'confirmImport'])
                ->name('recursos.importar.confirmar');
            Route::resource('recursos', RecursoController::class);
            Route::resource('subespacios', SubEspacioController::class);
            Route::resource('usuarios', UserController::class);
        });
});
require __DIR__.'/auth.php';