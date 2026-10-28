<?php

use App\Models\Historial;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class, RefreshDatabase::class);

test('history remains one paginated timeline and supports search date and user filters', function () {
    $adminRole = Rol::firstOrCreate(['name' => 'Administrador']);
    $admin = User::factory()->create(['role_id' => $adminRole->id]);
    $actor = User::factory()->create();

    foreach (range(0, 24) as $index) {
        Historial::create([
            'responsable_id' => $actor->id,
            'solicitante_id' => $actor->id,
            'evento_nombre' => "Actividad de inventario {$index}",
            'descripcion' => 'Registro de prueba',
            'espacio_nombre' => 'SUM',
            'fecha_realizacion' => now()->subDays($index),
        ]);
    }

    $this->actingAs($admin)
        ->get(route('historial.index'))
        ->assertOk()
        ->assertSee('Mostrando 1–10 de 25 registros')
        ->assertSee('Actividad de inventario 0')
        ->assertDontSee('Actividad de inventario 24')
        ->assertSee('name="desde"', false)
        ->assertSee('name="hasta"', false)
        ->assertSee('name="usuario_id"', false);

    $this->get(route('historial.index', [
        'buscar' => 'Actividad de inventario 24',
        'desde' => now()->subDays(24)->toDateString(),
        'hasta' => now()->subDays(24)->toDateString(),
        'usuario_id' => $actor->id,
    ]))
        ->assertOk()
        ->assertSee('Actividad de inventario 24')
        ->assertSee('Mostrando 1–1 de 1 registros');

    $this->get(route('historial.index', ['hasta' => now()->subDays(1)->toDateString()]))
        ->assertOk()
        ->assertSee('Mostrando 1–10 de 24 registros');
});
