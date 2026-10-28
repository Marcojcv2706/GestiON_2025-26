<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolSeeder::class,
            UserSeeder::class,
            RecursoSeeder::class,
            EspacioSeeder::class,
            SubEspacioSeeder::class,
            SolicitudEventoSeeder::class,
            HistorialSeeder::class,
        ]);
    }
}
