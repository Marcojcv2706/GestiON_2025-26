<?php

use App\Models\Espacio;
use App\Models\Historial;
use App\Models\HistorialRecurso;
use App\Models\Recurso;
use App\Models\Rol;
use App\Models\SolicitudEvento;
use App\Models\SolicitudRecurso;
use App\Models\SubEspacio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $userWithRole = fn (string $name): User => User::factory()->create([
        'role_id' => Rol::firstOrCreate(['name' => $name])->id,
    ]);

    $this->admin = $userWithRole('Administrador');
    $this->teacher = $userWithRole('Profesor');
    $this->otherTeacher = $userWithRole('Profesor');
    $this->student = $userWithRole('Usuario');
    $this->otherStudent = $userWithRole('Usuario');
    $this->space = Espacio::create(['name' => 'SUM']);
    $this->subspace = SubEspacio::create([
        'name' => 'Cancha principal',
        'espacio_id' => $this->space->id,
    ]);
    $this->resource = Recurso::create([
        'name' => 'Proyector',
        'tipo' => 'Audiovisuales',
        'cantidad_total' => 5,
        'cantidad_disponible' => 5,
    ]);

    $this->eventPayload = [
        'name' => 'Campeonato',
        'description' => 'Torneo de prueba',
        'start_time' => now()->addDays(4)->setTime(10, 0)->format('Y-m-d\TH:i'),
        'end_time' => now()->addDays(4)->setTime(11, 0)->format('Y-m-d\TH:i'),
        'espacio_id' => $this->space->id,
        'sub_espacios' => [$this->subspace->id],
        'recursos' => [],
    ];
    $this->createEventRequest = function (User $requester, array $payload = []) {
        $this->actingAs($requester)
            ->post(route('solicitudes.store'), array_replace($this->eventPayload, $payload))
            ->assertRedirect(route('solicitudes.index'))
            ->assertSessionHas('success');

        return SolicitudEvento::where('user_id', $requester->id)->latest('id')->firstOrFail();
    };
    $this->createResourceRequest = function (User $requester, array $payload = []) {
        $this->actingAs($requester)
            ->post(route('solicitudes-recursos.store'), array_replace([
                'fecha_uso' => now()->addDays(3)->toDateString(),
                'motivo' => 'Presentación de clase',
                'recursos' => [$this->resource->id => 2],
            ], $payload))
            ->assertRedirect(route('solicitudes-recursos.index'))
            ->assertSessionHas('success');

        return SolicitudRecurso::where('user_id', $requester->id)->latest('id')->firstOrFail();
    };
});

test('administrators see both request types and can filter by type and status', function () {
    ($this->createEventRequest)($this->teacher);
    ($this->createResourceRequest)($this->student);

    $all = $this->actingAs($this->admin)->get(route('solicitudes.index'));
    $all->assertOk()
        ->assertSee('Campeonato')
        ->assertSee('EVENTO')
        ->assertSee('IMPLEMENTO')
        ->assertSee('Proyector');
    expect(substr_count($all->getContent(), 'EVENTO'))->toBe(1)
        ->and(substr_count($all->getContent(), 'IMPLEMENTO'))->toBe(1);

    $this->get(route('solicitudes.index', ['tipo' => 'eventos']))
        ->assertOk()
        ->assertSee('Campeonato')
        ->assertDontSee('Proyector');
    $this->get(route('solicitudes.index', ['tipo' => 'implementos']))
        ->assertOk()
        ->assertSee('Proyector')
        ->assertDontSee('Campeonato');
    $this->get(route('solicitudes.index', ['estado' => SolicitudRecurso::APROBADA]))
        ->assertOk()
        ->assertDontSee('Campeonato')
        ->assertDontSee('Proyector');
});

test('teachers see only manageable resource requests and students have a personal request status page', function () {
    $ownEventRequest = ($this->createEventRequest)($this->teacher);
    ($this->createEventRequest)($this->otherTeacher, ['name' => 'Evento ajeno']);
    $ownResourceRequest = ($this->createResourceRequest)($this->student);
    ($this->createResourceRequest)($this->otherStudent, ['motivo' => 'Solicitud privada']);

    $teacherList = $this->actingAs($this->teacher)->get(route('solicitudes.index'));
    $teacherList->assertOk()
        ->assertSee('Solicitud privada')
        ->assertDontSee('Campeonato')
        ->assertDontSee('Evento ajeno')
        ->assertDontSee('name="tipo"', false);
    $this->get(route('solicitudes.index', ['tipo' => 'eventos']))
        ->assertOk()
        ->assertSee('Solicitud privada')
        ->assertDontSee('Campeonato')
        ->assertDontSee('name="tipo"', false);
    $this->get(route('solicitudes.show', $ownEventRequest))->assertForbidden();
    $this->getJson(route('solicitudes.show', $ownEventRequest))->assertForbidden();

    $this->actingAs($this->student)->get(route('solicitudes.index'))->assertForbidden();
    $studentList = $this->get(route('solicitudes-recursos.index'));
    $studentList->assertOk()
        ->assertSee('Mis solicitudes de implementos')
        ->assertSee('Proyector')
        ->assertDontSee('Solicitud privada')
        ->assertDontSee('Campeonato');

    $this->get(route('solicitudes-recursos.show', $ownResourceRequest))
        ->assertOk()
        ->assertSee('Presentación de clase');
    $this->get(route('solicitudes-recursos.show', SolicitudRecurso::latest('id')->first()))
        ->assertForbidden();
});

test('teachers can approve resource requests but cannot review events or administer other modules', function () {
    $eventRequest = ($this->createEventRequest)($this->teacher);
    $resourceRequest = ($this->createResourceRequest)($this->student);

    $this->actingAs($this->teacher)
        ->get(route('solicitudes-recursos.show', $resourceRequest))
        ->assertOk()
        ->assertSee('Aprobar y reservar');
    $this->post(route('admin.solicitudes.aprobar', $eventRequest))->assertForbidden();
    $this->post(route('admin.solicitudes.rechazar', $eventRequest), ['motivo' => 'No procede.'])
        ->assertForbidden();
    $this->get('/admin/espacios')->assertForbidden();
    $this->get('/admin/recursos')->assertForbidden();
    $this->get('/admin/usuarios')->assertForbidden();

    $this->post(route('admin.solicitudes-recursos.aprobar', $resourceRequest))
        ->assertRedirect(route('solicitudes-recursos.show', $resourceRequest))
        ->assertSessionHas('success', 'La solicitud de implementos fue aprobada y los implementos quedaron reservados.');

    $resourceRequest->refresh();
    expect($resourceRequest->estado)->toBe(SolicitudRecurso::APROBADA)
        ->and($resourceRequest->revisado_por)->toBe($this->teacher->id)
        ->and($resourceRequest->decidido_en)->not->toBeNull()
        ->and($this->resource->fresh()->cantidad_disponible)->toBe(3);

    $history = Historial::where('evento_nombre', 'Solicitud de implementos #'.$resourceRequest->id)->firstOrFail();
    expect($history->responsable_id)->toBe($this->teacher->id)
        ->and($history->solicitante_id)->toBe($this->student->id);
    $this->assertDatabaseHas('historial_recursos', [
        'historial_id' => $history->id,
        'recurso_id' => $this->resource->id,
        'solicitante_id' => $this->student->id,
        'cantidad' => 2,
    ]);
    Historial::create([
        'responsable_id' => $this->teacher->id,
        'solicitante_id' => $this->teacher->id,
        'evento_nombre' => 'Actividad fuera de solicitudes de implementos',
        'descripcion' => 'No debe aparecer en el historial de gestión del profesor.',
        'fecha_realizacion' => now(),
    ]);
    $this->get(route('historial.index'))
        ->assertOk()
        ->assertSee('Solicitud de implementos #'.$resourceRequest->id)
        ->assertSee('Proyector')
        ->assertSee($this->student->name)
        ->assertDontSee('Actividad fuera de solicitudes de implementos');
    $this->getJson(route('api.calendario.index'))
        ->assertOk()
        ->assertJsonMissing(['title' => 'Solicitud de implementos #'.$resourceRequest->id]);
    $this->post(route('admin.solicitudes-recursos.aprobar', $resourceRequest))
        ->assertSessionHasErrors('solicitud');
    expect(HistorialRecurso::count())->toBe(1)
        ->and($this->resource->fresh()->cantidad_disponible)->toBe(3);
});

test('teachers can reject resource requests with a reason without changing inventory', function () {
    $resourceRequest = ($this->createResourceRequest)($this->student);

    $this->actingAs($this->teacher)
        ->post(route('admin.solicitudes-recursos.rechazar', $resourceRequest), ['observacion_admin' => ''])
        ->assertSessionHasErrors('observacion_admin');
    $this->post(route('admin.solicitudes-recursos.rechazar', $resourceRequest), [
        'observacion_admin' => 'No hay unidades disponibles.',
    ])->assertRedirect(route('solicitudes-recursos.show', $resourceRequest))
        ->assertSessionHas('success', 'La solicitud de implementos fue rechazada: No hay unidades disponibles.');

    $resourceRequest->refresh();
    expect($resourceRequest->estado)->toBe(SolicitudRecurso::RECHAZADA)
        ->and($resourceRequest->revisor->id)->toBe($this->teacher->id)
        ->and($resourceRequest->decidido_en)->not->toBeNull()
        ->and($resourceRequest->observacion_admin)->toBe('No hay unidades disponibles.')
        ->and($this->resource->fresh()->cantidad_disponible)->toBe(5)
        ->and(HistorialRecurso::count())->toBe(0);

    $this->actingAs($this->student)
        ->get(route('solicitudes-recursos.show', $resourceRequest))
        ->assertOk()
        ->assertSee('Tu solicitud de implementos fue rechazada.')
        ->assertSee('No hay unidades disponibles.');
});

test('resource approvals recheck stock and reject requests that would overdraw inventory', function () {
    $first = ($this->createResourceRequest)($this->student, [
        'recursos' => [$this->resource->id => 3],
    ]);
    $second = ($this->createResourceRequest)($this->otherStudent, [
        'recursos' => [$this->resource->id => 3],
    ]);

    $this->actingAs($this->teacher)
        ->post(route('admin.solicitudes-recursos.aprobar', $first))
        ->assertSessionHas('success');
    $this->post(route('admin.solicitudes-recursos.aprobar', $second))
        ->assertSessionHasErrors('recursos');

    expect($first->fresh()->estado)->toBe(SolicitudRecurso::APROBADA)
        ->and($second->fresh()->estado)->toBe(SolicitudRecurso::PENDIENTE)
        ->and($this->resource->fresh()->cantidad_disponible)->toBe(2)
        ->and(HistorialRecurso::count())->toBe(1);
});
