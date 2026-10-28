<?php

namespace Database\Seeders;

use App\Models\Rol;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'name' => 'Francisco Usandivares',
                'email' => 'francisco.usandivares@educacionadventista.org.ar',
                'role' => 'Administrador',
            ],
            [
                'name' => 'Marco Castro',
                'email' => 'marco.jcv.27@gmail.com',
                'role' => 'Administrador',
            ],
            [
                'name' => 'Profesor Ejemplo',
                'email' => 'profesor@ejemplo.com',
                'role' => 'Profesor',
            ],
            [
                'name' => 'Alumno Ejemplo',
                'email' => 'alumno@ejemplo.com',
                'role' => 'Usuario',
            ],
        ];

        foreach ($users as $user) {
            User::firstOrCreate(
                ['email' => $user['email']],
                [
                    'name' => $user['name'],
                    'password' => Hash::make('password'),
                    'role_id' => Rol::where('name', $user['role'])->firstOrFail()->id,
                ]
            );
        }
    }
}
