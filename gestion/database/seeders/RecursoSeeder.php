<?php

namespace Database\Seeders;

use App\Models\Recurso;
use Illuminate\Database\Seeder;

class RecursoSeeder extends Seeder
{
    public function run(): void
    {
        $recursos = [
            [
                'name' => 'Proyector salón de actos',
                'tipo' => 'Audiovisuales',
                'descripcion' => 'Proyector para presentaciones y actividades institucionales.',
                'cantidad_total' => 1,
            ],
            [
                'name' => 'Equipo de sonido',
                'tipo' => 'Audiovisuales',
                'descripcion' => 'Equipo de sonido portátil.',
                'cantidad_total' => 1,
            ],
            [
                'name' => 'Pelota de vóley',
                'tipo' => 'Deportivos',
                'descripcion' => 'Pelotas para actividades de vóley.',
                'cantidad_total' => 4,
            ],
            [
                'name' => 'Pelota de fútbol',
                'tipo' => 'Deportivos',
                'descripcion' => 'Pelotas para actividades de fútbol.',
                'cantidad_total' => 2,
            ],
            [
                'name' => 'Pelota de futsal',
                'tipo' => 'Deportivos',
                'descripcion' => 'Pelotas para actividades de futsal.',
                'cantidad_total' => 2,
            ],
            [
                'name' => 'Guitarra',
                'tipo' => 'Otros',
                'descripcion' => 'Instrumentos disponibles para actividades musicales.',
                'cantidad_total' => 2,
            ],
            [
                'name' => 'Piano',
                'tipo' => 'Otros',
                'descripcion' => 'Pianos disponibles en el pabellón de música.',
                'cantidad_total' => 2,
            ],
            [
                'name' => 'Cajón peruano',
                'tipo' => 'Otros',
                'descripcion' => 'Instrumento de percusión.',
                'cantidad_total' => 1,
            ],
            [
                'name' => 'Conos deportivos',
                'tipo' => 'Deportivos',
                'descripcion' => 'Conos para delimitar espacios y circuitos.',
                'cantidad_total' => 40,
            ],
            [
                'name' => 'Petos deportivos',
                'tipo' => 'Deportivos',
                'descripcion' => 'Petos para identificar equipos.',
                'cantidad_total' => 25,
            ],
        ];

        foreach ($recursos as $recurso) {
            Recurso::firstOrCreate(
                ['name' => $recurso['name']],
                [
                    ...$recurso,
                    'cantidad_disponible' => $recurso['cantidad_total'],
                ]
            );
        }
    }
}
