<?php

namespace Database\Seeders;

use App\Models\Rol;
use Illuminate\Database\Seeder;

class RolSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['Administrador', 'Profesor', 'Usuario'] as $name) {
            Rol::firstOrCreate(['name' => $name]);
        }
    }
}
