<?php

use App\Models\Espacio;
use App\Models\Evento;
use App\Models\Recurso;
use App\Models\Rol;
use App\Models\SolicitudEvento;
use App\Models\SolicitudEventoDecision;
use App\Models\SubEspacio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->admin = userWithRole('Administrador');
    $this->teacher = userWithRole('Profesor');
    $this->otherTeacher = userWithRole('Profesor');
    $this->otherUser = userWithRole('Usuario');
    $this->space = Espacio::create(['name' => 'SUM']);
    $this->subspace = SubEspacio::create(['name' => 'Cancha principal', 'espacio_id' => $this->space->id]);
    $this->secondSubspace = SubEspacio::create(['name' => 'SUM 2', 'espacio_id' => $this->space->id]);
    $this->resource = Recurso::create([
        'name' => 'Pelota',
        'tipo' => 'Deportivos',
        'cantidad_total' => 10,
        'cantidad_disponible' => 10,
    ]);
    $this->payload = [
        'name' => 'Campeonato',
        'description' => 'Torneo de prueba',
        'start_time' => now()->addDays(3)->setTime(10, 0)->format('Y-m-d\TH:i'),
        'end_time' => now()->addDays(3)->setTime(12, 0)->format('Y-m-d\TH:i'),
        'espacio_id' => $this->space->id,
        'sub_espacios' => [$this->subspace->id],
        'recursos' => [['id' => $this->resource->id, 'cantidad' => 6]],
    ];
    $this->createRequest = function (User $user, array $payload): SolicitudEvento {
        $this->actingAs($user)->post(route('solicitudes.store'), $payload)->assertRedirect();

        return SolicitudEvento::where('user_id', $user->id)->latest('id')->firstOrFail();
    };
});

function userWithRole(string $roleName): User
{
    $role = Rol::firstOrCreate(['name' => $roleName]);

    return User::factory()->create(['role_id' => $role->id]);
}

test('a single request can reserve one, several, all, or no subspaces', function () {
    $sumSubspaces = collect([$this->subspace, $this->secondSubspace]);
    foreach (['SUM 3', 'SUM 4'] as $name) {
        $sumSubspaces->push(SubEspacio::create(['name' => $name, 'espacio_id' => $this->space->id]));
    }

    $exterior = Espacio::create(['name' => 'Exteriores']);
    $exteriorSubspaces = collect(['Exterior 1', 'Exterior 2'])->map(
        fn ($name) => SubEspacio::create(['name' => $name, 'espacio_id' => $exterior->id])
    );
    $withoutSubspaces = Espacio::create(['name' => 'Salón de Actos']);

    $this->actingAs($this->teacher)
        ->from(route('solicitudes.create'))
        ->post(route('solicitudes.store'), array_replace($this->payload, ['sub_espacios' => []]))
        ->assertRedirect(route('solicitudes.create'))
        ->assertSessionHasErrors('sub_espacios');

    $single = ($this->createRequest)($this->teacher, array_replace($this->payload, [
        'sub_espacios' => [$sumSubspaces[0]->id],
    ]));
    $partial = ($this->createRequest)($this->teacher, array_replace($this->payload, [
        'name' => 'Encuentro parcial',
        'sub_espacios' => [$sumSubspaces[0]->id, $sumSubspaces[1]->id],
    ]));
    $all = ($this->createRequest)($this->teacher, array_replace($this->payload, [
        'name' => 'Encuentro completo',
        'sub_espacios' => $sumSubspaces->pluck('id')->all(),
    ]));
    $outdoors = ($this->createRequest)($this->teacher, array_replace($this->payload, [
        'name' => 'Campeonato exterior',
        'espacio_id' => $exterior->id,
        'sub_espacios' => $exteriorSubspaces->pluck('id')->all(),
    ]));
    $noSubspaces = ($this->createRequest)($this->teacher, array_replace($this->payload, [
        'name' => 'Acto institucional',
        'espacio_id' => $withoutSubspaces->id,
        'sub_espacios' => [],
    ]));

    expect($single->subEspacios->modelKeys())->toBe([$sumSubspaces[0]->id])
        ->and($partial->subEspacios->modelKeys())->toBe([$sumSubspaces[0]->id, $sumSubspaces[1]->id])
        ->and($all->subEspacios)->toHaveCount(4)
        ->and($outdoors->subEspacios)->toHaveCount(2)
        ->and($noSubspaces->subEspacios)->toBeEmpty()
        ->and(SolicitudEvento::count())->toBe(5)
        ->and(Evento::count())->toBe(0);
});

test('teachers submit requests without creating events or reserving inventory and only see their own', function () {
    $own = ($this->createRequest)($this->teacher, $this->payload);
    $other = ($this->createRequest)($this->otherTeacher, array_replace($this->payload, ['name' => 'Solicitud privada']));

    expect($own->estado)->toBe(SolicitudEvento::PENDIENTE)
        ->and($own->evento_id)->toBeNull()
        ->and($this->resource->fresh()->cantidad_disponible)->toBe(10)
        ->and(Evento::count())->toBe(0);

    $this->actingAs($this->teacher)
        ->get(route('solicitudes.index'))
        ->assertOk()
        ->assertSee('Campeonato')
        ->assertDontSee('Solicitud privada');

    $this->get(route('solicitudes.show', $own))
        ->assertOk()
        ->assertSee('Campeonato')
        ->assertSee('Cancha principal');

    $this->get(route('solicitudes.show', $other))->assertForbidden();
    $this->post('/eventos', $this->payload)->assertForbidden();

    $this->actingAs($this->otherUser)
        ->get(route('solicitudes.index'))
        ->assertForbidden();
    $this->get(route('solicitudes.create'))->assertForbidden();
    $this->post(route('solicitudes.store'), $this->payload)->assertForbidden();
});

test('approval checks every requested subspace and creates one calendar event', function () {
    $thirdSubspace = SubEspacio::create(['name' => 'SUM 3', 'espacio_id' => $this->space->id]);
    $multiRequest = ($this->createRequest)($this->teacher, array_replace($this->payload, [
        'sub_espacios' => [$this->subspace->id, $this->secondSubspace->id],
    ]));
    $this->actingAs($this->admin)
        ->post(route('admin.solicitudes.aprobar', $multiRequest))
        ->assertRedirect(route('solicitudes.show', $multiRequest));

    $conflictingRequest = ($this->createRequest)($this->otherTeacher, array_replace($this->payload, [
        'name' => 'Comparte SUM 2',
        'sub_espacios' => [$this->secondSubspace->id],
    ]));
    $this->actingAs($this->admin)
        ->from(route('solicitudes.show', $conflictingRequest))
        ->post(route('admin.solicitudes.aprobar', $conflictingRequest))
        ->assertSessionHasErrors('start_time');
    expect($conflictingRequest->fresh()->estado)->toBe(SolicitudEvento::PENDIENTE);

    $independentRequest = ($this->createRequest)($this->otherTeacher, array_replace($this->payload, [
        'name' => 'Usa SUM 3',
        'sub_espacios' => [$thirdSubspace->id],
        'recursos' => [],
    ]));
    $this->actingAs($this->admin)
        ->post(route('admin.solicitudes.aprobar', $independentRequest))
        ->assertRedirect(route('solicitudes.show', $independentRequest));

    $events = $this->getJson(route('api.calendario.index'))->assertOk()->json();
    $calendarEvents = collect($events)->filter(fn ($event) => str_starts_with($event['id'], 'evento-'));
    $multiEventId = $multiRequest->fresh()->evento_id;
    expect($multiRequest->fresh()->evento_id)->not->toBeNull()
        ->and($multiRequest->fresh()->evento->subEspacios->modelKeys())
        ->toBe([$this->subspace->id, $this->secondSubspace->id])
        ->and($calendarEvents)->toHaveCount(2)
        ->and($calendarEvents->where('id', 'evento-'.$multiEventId))->toHaveCount(1)
        ->and(collect($events)->firstWhere('id', 'evento-'.$multiEventId)['extendedProps']['espacio'])
        ->toBe('SUM · Cancha principal + SUM 2')
        ->and(Evento::count())->toBe(2);
});

test('request corrections synchronize added and removed subspaces on the same request', function () {
    $solicitud = ($this->createRequest)($this->teacher, $this->payload);
    $this->actingAs($this->admin)->post(route('admin.solicitudes.solicitar-modificacion', $solicitud), [
        'motivo' => 'Agrega o cambia los subespacios.',
    ])->assertRedirect();

    $expanded = array_replace($this->payload, [
        'sub_espacios' => [$this->subspace->id, $this->secondSubspace->id],
    ]);
    $this->actingAs($this->teacher)
        ->put(route('solicitudes.update', $solicitud), $expanded)
        ->assertRedirect();
    expect($solicitud->fresh()->subEspacios->modelKeys())
        ->toBe([$this->subspace->id, $this->secondSubspace->id]);

    $this->put(route('solicitudes.update', $solicitud), array_replace($expanded, [
        'sub_espacios' => [$this->secondSubspace->id],
    ]))->assertRedirect();
    $this->post(route('solicitudes.reenviar', $solicitud))->assertRedirect();

    expect(SolicitudEvento::count())->toBe(1)
        ->and($solicitud->fresh()->subEspacios->modelKeys())->toBe([$this->secondSubspace->id])
        ->and($solicitud->fresh()->estado)->toBe(SolicitudEvento::PENDIENTE);
});

test('only admin approval creates a calendar event and reserves resources', function () {
    $solicitud = ($this->createRequest)($this->teacher, $this->payload);

    $this->actingAs($this->admin)
        ->post(route('admin.solicitudes.aprobar', $solicitud))
        ->assertRedirect(route('solicitudes.show', $solicitud));

    $solicitud->refresh();
    expect($solicitud->estado)->toBe(SolicitudEvento::APROBADA)
        ->and($solicitud->evento_id)->not->toBeNull()
        ->and($this->resource->fresh()->cantidad_disponible)->toBe(4);
    $this->assertDatabaseHas('solicitud_evento_decisiones', [
        'solicitud_evento_id' => $solicitud->id,
        'actor_id' => $this->admin->id,
        'accion' => 'aprobada',
    ]);

    $this->getJson(route('api.calendario.index'))
        ->assertOk()
        ->assertJsonFragment(['title' => 'Campeonato']);
});

test('rejection and modification require a reason and keep request out of the calendar', function () {
    $solicitud = ($this->createRequest)($this->teacher, $this->payload);

    $this->actingAs($this->admin)
        ->post(route('admin.solicitudes.rechazar', $solicitud), ['motivo' => ''])
        ->assertSessionHasErrors('motivo');
    expect($solicitud->fresh()->estado)->toBe(SolicitudEvento::PENDIENTE);

    $this->post(route('admin.solicitudes.solicitar-modificacion', $solicitud), [
        'motivo' => 'Elija otro horario.',
    ])->assertRedirect();
    expect($solicitud->fresh()->estado)->toBe(SolicitudEvento::MODIFICACION_SOLICITADA);

    $this->getJson(route('api.calendario.index'))
        ->assertOk()
        ->assertJsonMissing(['title' => 'Campeonato']);

    $this->actingAs($this->teacher)
        ->put(route('solicitudes.update', $solicitud), array_replace($this->payload, [
            'start_time' => now()->addDays(4)->setTime(10, 0)->format('Y-m-d\TH:i'),
            'end_time' => now()->addDays(4)->setTime(12, 0)->format('Y-m-d\TH:i'),
        ]))->assertRedirect();
    $this->post(route('solicitudes.reenviar', $solicitud))->assertRedirect();

    expect($solicitud->fresh()->estado)->toBe(SolicitudEvento::PENDIENTE)
        ->and(SolicitudEventoDecision::where('solicitud_evento_id', $solicitud->id)->count())->toBe(4);
});

test('approval rechecks current resource stock and subspace conflicts atomically', function () {
    $solicitud = ($this->createRequest)($this->teacher, $this->payload);
    $this->resource->update(['cantidad_disponible' => 5]);

    $this->actingAs($this->admin)
        ->from(route('solicitudes.show', $solicitud))
        ->post(route('admin.solicitudes.aprobar', $solicitud))
        ->assertRedirect(route('solicitudes.show', $solicitud))
        ->assertSessionHasErrors('recursos');
    expect($solicitud->fresh()->estado)->toBe(SolicitudEvento::PENDIENTE)
        ->and(Evento::count())->toBe(0)
        ->and($this->resource->fresh()->cantidad_disponible)->toBe(5);

    $this->resource->update(['cantidad_disponible' => 10]);
    Evento::create([
        'name' => 'Evento ocupado',
        'start_time' => $this->payload['start_time'],
        'end_time' => $this->payload['end_time'],
        'espacio_id' => $this->space->id,
        'sub_espacio_id' => $this->subspace->id,
        'user_id' => $this->admin->id,
        'estado' => 'programado',
    ]);
    $this->post(route('admin.solicitudes.aprobar', $solicitud))
        ->assertSessionHasErrors('start_time');

    expect($solicitud->fresh()->estado)->toBe(SolicitudEvento::PENDIENTE)
        ->and(Evento::count())->toBe(1);
});

test('admin direct edits require a reason and are recorded', function () {
    $solicitud = ($this->createRequest)($this->teacher, $this->payload);
    $updated = array_replace($this->payload, [
        'name' => 'Campeonato actualizado',
        'start_time' => now()->addDays(5)->setTime(11, 0)->format('Y-m-d\TH:i'),
        'end_time' => now()->addDays(5)->setTime(13, 0)->format('Y-m-d\TH:i'),
        'sub_espacios' => [$this->subspace->id, $this->secondSubspace->id],
    ]);

    $this->actingAs($this->admin)
        ->put(route('admin.solicitudes.modificar', $solicitud), $updated)
        ->assertSessionHasErrors('motivo');

    $this->put(route('admin.solicitudes.modificar', $solicitud), [...$updated, 'motivo' => 'Horario alternativo disponible.'])
        ->assertRedirect();

    $decision = $solicitud->fresh()->decisiones()
        ->where('accion', 'solicitud_modificada_por_admin')
        ->firstOrFail();

    expect($solicitud->fresh()->name)->toBe('Campeonato actualizado')
        ->and($solicitud->fresh()->estado)->toBe(SolicitudEvento::PENDIENTE)
        ->and($decision->cambios['sub_espacios']['anterior'])->toBe(['Cancha principal'])
        ->and($decision->cambios['sub_espacios']['nuevo'])->toBe(['Cancha principal', 'SUM 2']);
});
