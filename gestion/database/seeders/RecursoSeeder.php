<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Recurso;

class RecursoSeeder extends Seeder
{
    public function run()
    {
        Recurso::create(['name' => 'Proyector Salon de Actos']);
        Recurso::create(['name' => 'Equipo de Sonido SUM']);
        
        Recurso::create(['name' => 'Pelota Voley 1']);
        Recurso::create(['name' => 'Pelota Voley 2']);
        Recurso::create(['name' => 'Pelota Voley 3']);
        Recurso::create(['name' => 'Pelota Voley 4']);

        Recurso::create(['name' => 'Pelota Futbol 1']);
        Recurso::create(['name' => 'Pelota Futbol 2']);

        Recurso::create(['name' => 'Pelota Futsal 1']);
        Recurso::create(['name' => 'Pelota Futsal 2']);

        Recurso::create(['name' => 'Guitarra 1']);
        Recurso::create(['name' => 'Guitarra 2']);
        Recurso::create(['name' => 'Piano 1']);
        Recurso::create(['name' => 'Piano 2']);
        Recurso::create(['name' => 'Cajon Peruano']);
    }
}