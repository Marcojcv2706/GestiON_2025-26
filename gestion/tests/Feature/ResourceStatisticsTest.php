<?php

use App\Models\Espacio;
use App\Models\Evento;
use App\Models\Historial;
use App\Models\HistorialRecurso;
use App\Models\Recurso;
use App\Models\Rol;
use App\Models\SubEspacio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class, RefreshDatabase::class);

test('resource report aggregates real history and applies date and resource filters', function () {
    $adminRole = Rol::firstOrCreate(['name' => 'Administrador']);
    $admin = User::factory()->create(['role_id' => $adminRole->id]);
    $requester = User::factory()->create(['name' => 'Ana Solicitante']);
    $space = Espacio::create(['name' => 'Gimnasio']);
    $subspace = SubEspacio::create(['name' => 'Cancha principal', 'espacio_id' => $space->id]);
    $resource = Recurso::create([
        'name' => 'Pelota histórica',
        'tipo' => 'Deportivos',
        'cantidad_total' => 20,
        'cantidad_disponible' => 20,
    ]);
    $event = Evento::create([
        'name' => 'Partido finalizado',
        'start_time' => '2026-03-15 10:00:00',
        'end_time' => '2026-03-15 12:00:00',
        'espacio_id' => $space->id,
        'user_id' => $requester->id,
        'estado' => 'finalizado',
    ]);
    $event->subEspacios()->attach($subspace->id);

    $history = Historial::create([
        'evento_id' => $event->id,
        'responsable_id' => $requester->id,
        'solicitante_id' => $requester->id,
        'evento_nombre' => $event->name,
        'espacio_nombre' => 'Gimnasio · Cancha principal',
        'fecha_realizacion' => '2026-03-15 12:00:00',
    ]);
    HistorialRecurso::create([
        'historial_id' => $history->id,
        'recurso_id' => $resource->id,
        'solicitante_id' => $requester->id,
        'recurso_nombre' => 'Pelota histórica',
        'cantidad' => 3,
    ]);

    $secondHistory = Historial::create([
        'responsable_id' => $requester->id,
        'solicitante_id' => $requester->id,
        'evento_nombre' => 'Préstamo en gimnasio',
        'espacio_nombre' => 'Gimnasio',
        'fecha_realizacion' => '2026-03-20 12:00:00',
    ]);
    HistorialRecurso::create([
        'historial_id' => $secondHistory->id,
        'recurso_id' => $resource->id,
        'solicitante_id' => $requester->id,
        'recurso_nombre' => 'Pelota histórica',
        'cantidad' => 2,
    ]);

    $legacySubspaceHistory = Historial::create([
        'responsable_id' => $requester->id,
        'solicitante_id' => $requester->id,
        'evento_nombre' => 'Préstamo en subespacio histórico',
        'espacio_nombre' => 'Cancha principal',
        'fecha_realizacion' => '2026-03-25 12:00:00',
    ]);
    HistorialRecurso::create([
        'historial_id' => $legacySubspaceHistory->id,
        'recurso_id' => $resource->id,
        'solicitante_id' => $requester->id,
        'recurso_nombre' => 'Pelota histórica',
        'cantidad' => 4,
    ]);

    $response = $this->actingAs($admin)
        ->getJson(route('api.reportes.recursos', [
            'desde' => '2026-03-01',
            'espacio_id' => $space->id,
            'categoria' => 'Deportivos',
            'recurso_id' => $resource->id,
            'usuario_id' => $requester->id,
        ]))
        ->assertOk();

    $response
        ->assertJsonPath('filtros.periodo', 'mes')
        ->assertJsonPath('estadisticas.espacios.0.nombre', 'Gimnasio')
        ->assertJsonPath('estadisticas.espacios.0.usos', 3)
        ->assertJsonPath('estadisticas.espacios.0.solicitudes', 3)
        ->assertJsonPath('estadisticas.espacios.0.unidades', 9)
        ->assertJsonPath('estadisticas.subespacios.0.nombre', 'Cancha principal')
        ->assertJsonPath('estadisticas.subespacios.0.usos', 2)
        ->assertJsonPath('estadisticas.subespacios.0.unidades', 7)
        ->assertJsonPath('estadisticas.implementos.0.solicitudes', 3)
        ->assertJsonPath('estadisticas.implementos.0.unidades', 9)
        ->assertJsonPath('estadisticas.usuarios.0.nombre', 'Ana Solicitante')
        ->assertJsonPath('estadisticas.categorias.0.categoria', 'Deportivos')
        ->assertJsonPath('estadisticas.categorias.0.unidades', 9)
        ->assertJsonPath('estadisticas.periodos.0.periodo', '2026-03-01');

    $this->getJson(route('api.reportes.recursos', ['subespacio_id' => $subspace->id]))
        ->assertOk()
        ->assertJsonPath('estadisticas.espacios.0.usos', 2)
        ->assertJsonPath('estadisticas.implementos.0.unidades', 7);

    $this->getJson(route('api.reportes.recursos', ['periodo' => 'semana']))
        ->assertOk()
        ->assertJsonPath('estadisticas.periodos.0.periodo', '2026-03-09');

    $this->getJson(route('api.reportes.recursos', [
        'desde' => '2026-04-01',
        'hasta' => '2026-04-30',
    ]))
        ->assertOk()
        ->assertJsonPath('estadisticas.implementos', [])
        ->assertJsonPath('estadisticas.periodos', []);
});

test('resource report requires an administrator and validates its filters', function () {
    $user = User::factory()->create();

    $this->getJson(route('api.reportes.recursos'))
        ->assertUnauthorized();

    $this->actingAs($user)
        ->getJson(route('api.reportes.recursos'))
        ->assertForbidden();

    $adminRole = Rol::firstOrCreate(['name' => 'Administrador']);
    $admin = User::factory()->create(['role_id' => $adminRole->id]);

    $this->actingAs($admin)
        ->getJson(route('api.reportes.recursos', ['categoria' => 'Inventada']))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('categoria');
});
