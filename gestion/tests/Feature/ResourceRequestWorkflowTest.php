<?php

use App\Models\Recurso;
use App\Models\Rol;
use App\Models\SolicitudRecurso;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $role = fn (string $name): User => User::factory()->create([
        'role_id' => Rol::firstOrCreate(['name' => $name])->id,
    ]);

    $this->admin = $role('Administrador');
    $this->student = $role('Usuario');
    $this->teacher = $role('Profesor');
    $this->resource = Recurso::create([
        'name' => 'Proyector',
        'tipo' => 'Otros',
        'cantidad_total' => 5,
        'cantidad_disponible' => 5,
    ]);
    $this->payload = [
        'fecha_uso' => now()->addDays(3)->toDateString(),
        'motivo' => 'Presentación de clase',
        'recursos' => [$this->resource->id => 3],
    ];
});

test('student resource requests stay pending until approval and return restores inventory once', function () {
    $this->actingAs($this->student)
        ->post(route('solicitudes-recursos.store'), $this->payload)
        ->assertRedirect(route('solicitudes-recursos.index'))
        ->assertSessionHas('success');

    $request = SolicitudRecurso::with('recursos')->firstOrFail();
    expect($request->estado)->toBe(SolicitudRecurso::PENDIENTE)
        ->and($request->recursos->first()->pivot->cantidad)->toBe(3)
        ->and($this->resource->fresh()->cantidad_disponible)->toBe(5);

    $this->actingAs($this->admin)
        ->post(route('admin.solicitudes-recursos.aprobar', $request))
        ->assertSessionHas('success');
    expect($request->fresh()->estado)->toBe(SolicitudRecurso::APROBADA)
        ->and($this->resource->fresh()->cantidad_disponible)->toBe(2);

    $this->post(route('admin.solicitudes-recursos.devolver', $request))
        ->assertSessionHas('success');
    expect($request->fresh()->estado)->toBe(SolicitudRecurso::DEVUELTA)
        ->and($this->resource->fresh()->cantidad_disponible)->toBe(5);

    $this->post(route('admin.solicitudes-recursos.devolver', $request))
        ->assertSessionHasErrors('solicitud');
    expect($this->resource->fresh()->cantidad_disponible)->toBe(5);
});

test('resource requests require a valid date, reason, and at least one positive quantity', function () {
    $this->actingAs($this->student)
        ->from(route('solicitudes-recursos.create'))
        ->post(route('solicitudes-recursos.store'), [
            'fecha_uso' => now()->subDay()->toDateString(),
            'motivo' => '',
            'recursos' => [$this->resource->id => 1],
        ])
        ->assertRedirect(route('solicitudes-recursos.create'))
        ->assertSessionHasErrors(['fecha_uso', 'motivo']);

    $this->post(route('solicitudes-recursos.store'), [
        ...$this->payload,
        'recursos' => [$this->resource->id => 0],
    ])->assertSessionHasErrors('recursos');

    $this->post(route('solicitudes-recursos.store'), [
        ...$this->payload,
        'recursos' => [$this->resource->id => 6],
    ])->assertSessionHasErrors('recursos');

    expect(SolicitudRecurso::count())->toBe(0)
        ->and($this->resource->fresh()->cantidad_disponible)->toBe(5);
});

test('approval rechecks stock and rejection records a reason without changing inventory', function () {
    $this->actingAs($this->student)
        ->post(route('solicitudes-recursos.store'), $this->payload)
        ->assertRedirect();
    $request = SolicitudRecurso::firstOrFail();
    $this->resource->update(['cantidad_disponible' => 2]);

    $this->actingAs($this->admin)
        ->from(route('solicitudes-recursos.index'))
        ->post(route('admin.solicitudes-recursos.aprobar', $request))
        ->assertSessionHasErrors('recursos');
    expect($request->fresh()->estado)->toBe(SolicitudRecurso::PENDIENTE)
        ->and($this->resource->fresh()->cantidad_disponible)->toBe(2);

    $this->post(route('admin.solicitudes-recursos.rechazar', $request), [
        'observacion_admin' => 'No hay suficientes unidades.',
    ])->assertSessionHas('success');
    expect($request->fresh()->estado)->toBe(SolicitudRecurso::RECHAZADA)
        ->and($request->fresh()->observacion_admin)->toBe('No hay suficientes unidades.')
        ->and($this->resource->fresh()->cantidad_disponible)->toBe(2);
});

test('only students can request resources and only administrators can review them', function () {
    $this->actingAs($this->teacher)
        ->get(route('solicitudes-recursos.create'))
        ->assertForbidden();
    $this->post(route('solicitudes-recursos.store'), $this->payload)
        ->assertForbidden();
    $this->get(route('solicitudes-recursos.index'))->assertForbidden();

    $this->actingAs($this->admin)
        ->get(route('solicitudes-recursos.index'))
        ->assertOk();
});
