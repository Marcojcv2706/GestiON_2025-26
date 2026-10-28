<?php

namespace Database\Seeders;

use App\Models\Espacio;
use Illuminate\Database\Seeder;

class EspacioSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['SUM', 'Canchas Exteriores', 'Pabellón de Música', 'Salón de Actos'] as $name) {
            Espacio::firstOrCreate(['name' => $name]);
        }
    }
}
