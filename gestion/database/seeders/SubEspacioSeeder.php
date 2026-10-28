<?php

namespace Database\Seeders;

use App\Models\Espacio;
use App\Models\SubEspacio;
use Illuminate\Database\Seeder;

class SubEspacioSeeder extends Seeder
{
    public function run(): void
    {
        $subEspacios = [
            'SUM' => ['Cancha Voley 1', 'Cancha Voley 2', 'Cancha Futbol 5', 'Cancha Basquet'],
            'Canchas Exteriores' => ['Cancha Futbol 11', 'Cancha Tenis'],
        ];

        foreach ($subEspacios as $espacioNombre => $nombres) {
            $espacio = Espacio::where('name', $espacioNombre)->firstOrFail();

            foreach ($nombres as $name) {
                SubEspacio::firstOrCreate([
                    'espacio_id' => $espacio->id,
                    'name' => $name,
                ]);
            }
        }
    }
}
