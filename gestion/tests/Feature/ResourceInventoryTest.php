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
use Illuminate\Support\Facades\DB;

uses(Tests\TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $adminRole = Rol::firstOrCreate(['name' => 'Administrador']);
    $this->admin = User::factory()->create(['role_id' => $adminRole->id]);
    $this->espacio = Espacio::create(['name' => 'Gimnasio']);
    $this->subEspacio = SubEspacio::create([
        'name' => 'Cancha principal',
        'espacio_id' => $this->espacio->id,
    ]);
    $this->recurso = Recurso::create([
        'name' => 'Pelota de fútbol',
        'tipo' => 'Deportivos',
        'cantidad_total' => 20,
        'cantidad_disponible' => 20,
    ]);
    $this->actingAs($this->admin);

    $this->eventData = fn (array $overrides = []) => array_replace([
        'name' => 'Partido de práctica',
        'description' => 'Partido semanal',
        'start_time' => now()->addDay()->startOfHour()->format('Y-m-d\TH:i'),
        'end_time' => now()->addDay()->startOfHour()->addHour()->format('Y-m-d\TH:i'),
        'espacio_id' => $this->espacio->id,
        'sub_espacio_id' => $this->subEspacio->id,
        'recursos' => [[
            'id' => $this->recurso->id,
            'cantidad' => 5,
        ]],
    ], $overrides);
});

test('creating an event reserves the requested resource quantity', function () {
    $this->post(route('admin.eventos.store'), ($this->eventData)())
        ->assertRedirect(route('eventos.index'));

    expect($this->recurso->fresh()->cantidad_disponible)->toBe(15)
        ->and(Evento::first()->recursos()->first()->pivot->cantidad)->toBe(5);
});

test('sequential reservations reduce stock and reject requests above the remainder', function () {
    $this->post(route('admin.eventos.store'), ($this->eventData)());
    $this->post(route('admin.eventos.store'), ($this->eventData)([
        'name' => 'Segundo evento',
        'start_time' => now()->addDay()->startOfHour()->addHours(3)->format('Y-m-d\TH:i'),
        'end_time' => now()->addDay()->startOfHour()->addHours(4)->format('Y-m-d\TH:i'),
        'recursos' => [['id' => $this->recurso->id, 'cantidad' => 8]],
    ]))->assertRedirect(route('eventos.index'));

    expect($this->recurso->fresh()->cantidad_disponible)->toBe(7);

    $response = $this->from(route('admin.eventos.create'))
        ->post(route('admin.eventos.store'), ($this->eventData)([
            'recursos' => [['id' => $this->recurso->id, 'cantidad' => 8]],
        ]));

    $response->assertRedirect(route('admin.eventos.create'))
        ->assertSessionHasErrors('recursos');
    $this->assertStringContainsString('Cantidad disponible: 7', session('errors')->first('recursos'));

    $this->assertDatabaseCount('eventos', 2);
    expect($this->recurso->fresh()->cantidad_disponible)->toBe(7);
});

test('editing event quantities releases previous units and reserves the new quantity', function () {
    $this->post(route('admin.eventos.store'), ($this->eventData)());
    $evento = Evento::first();

    $this->put(route('admin.eventos.update', $evento), ($this->eventData)([
        'recursos' => [['id' => $this->recurso->id, 'cantidad' => 8]],
    ]))->assertRedirect(route('eventos.index'));

    expect($this->recurso->fresh()->cantidad_disponible)->toBe(12)
        ->and($evento->fresh()->recursos()->first()->pivot->cantidad)->toBe(8);
});

test('event subspace assignments can be added and removed without duplicating the event', function () {
    $secondSubspace = SubEspacio::create([
        'name' => 'Cancha secundaria',
        'espacio_id' => $this->espacio->id,
    ]);
    $singleSubspace = ($this->eventData)([
        'sub_espacios' => [$this->subEspacio->id],
    ]);
    unset($singleSubspace['sub_espacio_id']);
    $this->post(route('admin.eventos.store'), $singleSubspace)
        ->assertRedirect(route('eventos.index'));
    $evento = Evento::firstOrFail();

    $expanded = ($this->eventData)([
        'sub_espacios' => [$this->subEspacio->id, $secondSubspace->id],
    ]);
    unset($expanded['sub_espacio_id']);
    $this->put(route('admin.eventos.update', $evento), $expanded)
        ->assertRedirect(route('eventos.index'));
    expect($evento->fresh()->subEspacios->modelKeys())
        ->toBe([$this->subEspacio->id, $secondSubspace->id]);

    $reduced = ($this->eventData)([
        'sub_espacios' => [$secondSubspace->id],
    ]);
    unset($reduced['sub_espacio_id']);
    $this->put(route('admin.eventos.update', $evento), $reduced)
        ->assertRedirect(route('eventos.index'));

    expect(Evento::count())->toBe(1)
        ->and($evento->fresh()->subEspacios->modelKeys())->toBe([$secondSubspace->id])
        ->and($this->getJson(route('api.calendario.index'))->assertOk()->json())->toHaveCount(1);
});

test('cancelling an event restores reserved quantities', function () {
    $this->post(route('admin.eventos.store'), ($this->eventData)());
    $evento = Evento::first();

    $this->delete(route('admin.eventos.destroy', $evento))
        ->assertRedirect(route('eventos.index'));

    expect($this->recurso->fresh()->cantidad_disponible)->toBe(20);
});

test('finalizing an event restores inventory and records resource usage in history', function () {
    $this->post(route('admin.eventos.store'), ($this->eventData)([
        'start_time' => now()->subHours(3)->format('Y-m-d\TH:i'),
        'end_time' => now()->subHours(2)->format('Y-m-d\TH:i'),
    ]));
    $evento = Evento::first();

    $this->post(route('admin.eventos.finalizar', $evento))
        ->assertRedirect(route('eventos.index'));

    expect($this->recurso->fresh()->cantidad_disponible)->toBe(20)
        ->and($evento->fresh()->estado)->toBe('finalizado');

    $this->assertDatabaseHas('historials', [
        'evento_id' => $evento->id,
        'evento_nombre' => 'Partido de práctica',
    ]);
    $this->assertDatabaseHas('historial_recursos', [
        'recurso_id' => $this->recurso->id,
        'cantidad' => 5,
    ]);
    $this->get(route('historial.index'))
        ->assertOk()
        ->assertSee('Partido de práctica')
        ->assertSee('Pelota de fútbol');

    $this->from(route('eventos.index'))
        ->post(route('admin.eventos.finalizar', $evento))
        ->assertRedirect(route('eventos.index'))
        ->assertSessionHasErrors('evento');

    expect($this->recurso->fresh()->cantidad_disponible)->toBe(20)
        ->and(\App\Models\Historial::where('evento_id', $evento->id)->count())->toBe(1);
});

test('history stores every subspace used by a completed event', function () {
    $secondSubspace = SubEspacio::create([
        'name' => 'Cancha secundaria',
        'espacio_id' => $this->espacio->id,
    ]);
    $this->post(route('admin.eventos.store'), ($this->eventData)([
        'start_time' => now()->subHours(3)->format('Y-m-d\TH:i'),
        'end_time' => now()->subHours(2)->format('Y-m-d\TH:i'),
        'sub_espacios' => [$this->subEspacio->id, $secondSubspace->id],
    ]))->assertRedirect(route('eventos.index'));
    $evento = Evento::firstOrFail();

    $this->post(route('admin.eventos.finalizar', $evento))
        ->assertRedirect(route('eventos.index'));

    $this->assertDatabaseHas('historials', [
        'evento_id' => $evento->id,
        'espacio_nombre' => 'Gimnasio · Cancha principal + Cancha secundaria',
    ]);
});

test('resource creation and quantity changes keep available stock consistent', function () {
    $this->post(route('admin.recursos.store'), [
        'name' => 'Conos',
        'tipo' => 'Deportivos',
        'cantidad_total' => 30,
    ])->assertRedirect(route('admin.recursos.index'));

    $conos = Recurso::where('name', 'Conos')->firstOrFail();
    expect($conos->cantidad_total)->toBe(30)
        ->and($conos->cantidad_disponible)->toBe(30);

    $this->post(route('admin.eventos.store'), ($this->eventData)([
        'recursos' => [['id' => $this->recurso->id, 'cantidad' => 5]],
    ]));
    $this->put(route('admin.recursos.update', $this->recurso), [
        'name' => $this->recurso->name,
        'tipo' => 'Deportivos',
        'cantidad_total' => 25,
    ])->assertRedirect(route('admin.recursos.index'));

    expect($this->recurso->fresh()->cantidad_disponible)->toBe(20);

    $this->from(route('admin.recursos.edit', $this->recurso))
        ->put(route('admin.recursos.update', $this->recurso), [
            'name' => $this->recurso->name,
            'tipo' => 'Deportivos',
            'cantidad_total' => 4,
        ])
        ->assertRedirect(route('admin.recursos.edit', $this->recurso))
        ->assertSessionHasErrors('cantidad_total');
});

test('a subspace must belong to the selected space', function () {
    $anotherSpace = Espacio::create(['name' => 'Auditorio']);

    $this->from(route('admin.eventos.create'))
        ->post(route('admin.eventos.store'), ($this->eventData)([
            'espacio_id' => $anotherSpace->id,
        ]))
        ->assertRedirect(route('admin.eventos.create'))
        ->assertSessionHasErrors('sub_espacios.0');

    $this->assertDatabaseCount('eventos', 0);
});

test('event creation form explains location and quantity inputs', function () {
    $this->get(route('admin.eventos.create'))
        ->assertOk()
        ->assertSee('Nombre del evento')
        ->assertSee('Espacio')
        ->assertSee('Subespacio')
        ->assertSee('Cantidad')
        ->assertSee('Crear evento');
});

test('calendar data includes reserved resource quantities', function () {
    $this->post(route('admin.eventos.store'), ($this->eventData)());

    $this->getJson(route('api.calendario.index'))
        ->assertOk()
        ->assertJsonFragment([
            'recursos' => ['Pelota de fútbol (×5)'],
        ]);
});

test('resource inventory page displays stock counts to administrators', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.recursos.index'))
        ->assertOk()
        ->assertSee('Pelota de fútbol')
        ->assertSee('20')
        ->assertSee('Implementos')
        ->assertSee('En uso')
        ->assertSee('Filas por página')
        ->assertSee('Agregar implemento');

    $this->get(route('admin.recursos.create'))
        ->assertOk()
        ->assertSee('Nombre del implemento')
        ->assertSee('Categoría')
        ->assertSee('Cantidad total');
});

test('inventory search filters sort and paginate large lists on the server', function () {
    Recurso::create([
        'name' => 'Micrófono',
        'tipo' => 'Audiovisuales',
        'cantidad_total' => 2,
        'cantidad_disponible' => 0,
    ]);
    Recurso::create([
        'name' => 'Amplificador',
        'tipo' => 'Audiovisuales',
        'cantidad_total' => 4,
        'cantidad_disponible' => 3,
    ]);

    $now = now();
    $rows = [];
    for ($index = 0; $index < 1000; $index++) {
        $rows[] = [
            'name' => sprintf('Implemento %04d', $index),
            'tipo' => 'Otros',
            'cantidad_total' => 10,
            'cantidad_disponible' => 10,
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }

    foreach (array_chunk($rows, 250) as $chunk) {
        DB::table('recursos')->insert($chunk);
    }

    $page = $this->get(route('admin.recursos.index', ['por_pagina' => 100]));
    $page->assertOk()->assertSee('1,003 implementos');
    expect($page->viewData('recursos')->count())->toBe(100)
        ->and($page->viewData('recursos')->total())->toBe(1003);

    $sortedPage = $this->get(route('admin.recursos.index', [
        'tipo' => 'Otros',
        'orden' => 'name',
        'direccion' => 'desc',
        'por_pagina' => 10,
    ]))->assertOk();
    expect($sortedPage->viewData('recursos')->first()->name)->toBe('Implemento 0999');

    $this->get(route('admin.recursos.index', [
        'buscar' => 'Pelota',
        'tipo' => 'Deportivos',
        'estado' => 'disponible',
        'orden' => 'cantidad_disponible',
        'direccion' => 'desc',
    ]))
        ->assertOk()
        ->assertSee('Pelota de fútbol')
        ->assertDontSee('Implemento 0001')
        ->assertSee('Orden descendente');

    $this->get(route('admin.recursos.index', [
        'estado' => 'sin_unidades',
        'tipo' => 'Audiovisuales',
    ]))
        ->assertOk()
        ->assertSee('Micrófono')
        ->assertDontSee('Amplificador');
});

test('dashboard shows upcoming events without generic inventory statistics', function () {
    $this->post(route('admin.eventos.store'), ($this->eventData)());

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Partido de práctica')
        ->assertSee('Cancha principal')
        ->assertSee('Pelota de fútbol ×5')
        ->assertSee('Agregar evento')
        ->assertDontSee('Unidades disponibles')
        ->assertDontSee('15 unidades disponibles');
});

test('database seeding stores one resource per type with its stock quantity', function () {
    $this->seed();

    $voley = Recurso::where('name', 'Pelota de vóley')->firstOrFail();

    expect($voley->cantidad_total)->toBe(4)
        ->and($voley->cantidad_disponible)->toBe(4)
        ->and(Recurso::where('name', 'like', 'Pelota Voley %')->exists())->toBeFalse()
        ->and(Recurso::count())->toBe(10);

    $this->assertDatabaseHas('historial_recursos', [
        'recurso_id' => $voley->id,
        'recurso_nombre' => 'Pelota de vóley',
        'cantidad' => 3,
    ]);
});

test('legacy unit resources consolidate without losing active reservations or history quantities', function () {
    $legacyResources = collect(['Pelota Voley 1', 'Pelota Voley 2'])->map(fn ($name) => Recurso::create([
        'name' => $name,
        'tipo' => 'Deportivos',
        'cantidad_total' => 0,
        'cantidad_disponible' => 0,
    ]));

    $evento = Evento::create([
        'name' => 'Entrenamiento de vóley',
        'start_time' => now()->addDay(),
        'end_time' => now()->addDay()->addHour(),
        'espacio_id' => $this->espacio->id,
        'sub_espacio_id' => $this->subEspacio->id,
        'recurso_id' => $legacyResources->first()->id,
        'user_id' => $this->user->id,
        'estado' => 'programado',
    ]);
    $evento->recursos()->attach($legacyResources[0]->id, ['cantidad' => 1]);
    $evento->recursos()->attach($legacyResources[1]->id, ['cantidad' => 2]);

    $historial = Historial::create([
        'evento_id' => $evento->id,
        'responsable_id' => $this->user->id,
        'solicitante_id' => $this->user->id,
        'evento_nombre' => $evento->name,
    ]);
    foreach ($legacyResources as $index => $recurso) {
        HistorialRecurso::create([
            'historial_id' => $historial->id,
            'recurso_id' => $recurso->id,
            'solicitante_id' => $this->user->id,
            'recurso_nombre' => $recurso->name,
            'cantidad' => $index + 1,
        ]);
    }

    $migration = require database_path('migrations/2026_10_28_000001_consolidate_legacy_resource_units.php');
    $migration->up();

    $consolidated = Recurso::where('name', 'Pelota de vóley')->firstOrFail();
    expect($consolidated->cantidad_total)->toBe(3)
        ->and($consolidated->cantidad_disponible)->toBe(0)
        ->and($evento->fresh()->recurso_id)->toBe($consolidated->id)
        ->and($evento->fresh()->recursos)->toHaveCount(1)
        ->and($evento->fresh()->recursos->first()->pivot->cantidad)->toBe(3)
        ->and($historial->fresh()->recursos)->toHaveCount(1)
        ->and($historial->fresh()->recursos->first()->cantidad)->toBe(3)
        ->and(Recurso::where('name', 'like', 'Pelota Voley %')->exists())->toBeFalse();
});
