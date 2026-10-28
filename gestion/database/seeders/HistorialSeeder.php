<?php

namespace Database\Seeders;

use App\Models\Historial;
use App\Models\HistorialRecurso;
use App\Models\Recurso;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Database\Seeder;

class HistorialSeeder extends Seeder
{
    public function run(): void
    {
        $admin = $this->userForRole('Administrador');
        $profesor = $this->userForRole('Profesor');
        $usuario = $this->userForRole('Usuario');

        $historialVoley = $this->crearHistorial(
            $profesor,
            $profesor,
            'Entrenamiento de vóley',
            'Entrenamiento realizado en el espacio deportivo.',
            'Cancha Voley 1',
            '2026-10-22 19:00:00'
        );
        $this->agregarRecurso($historialVoley, 'Pelota de vóley', $profesor, 3);

        $historialMusica = $this->crearHistorial(
            $profesor,
            $profesor,
            'Evento musical',
            'Evento realizado con instrumentos musicales.',
            'Pabellón de Música',
            '2026-10-24 16:00:00'
        );
        $this->agregarRecurso($historialMusica, 'Guitarra', $profesor, 1);
        $this->agregarRecurso($historialMusica, 'Piano', $profesor, 1);

        $historialPrestamo = $this->crearHistorial(
            $profesor,
            $usuario,
            'Préstamo de implementos deportivos',
            'Préstamo de implementos para uso personal.',
            'SUM',
            '2026-10-23 18:00:00'
        );
        $this->agregarRecurso($historialPrestamo, 'Pelota de fútbol', $usuario, 1);
        $this->agregarRecurso($historialPrestamo, 'Pelota de futsal', $usuario, 1);

        $historialVoleyAdicional = $this->crearHistorial(
            $profesor,
            $usuario,
            'Préstamo de pelotas de vóley',
            'Préstamo de implementos para entrenamiento.',
            'Cancha Voley 2',
            '2026-10-25 17:00:00'
        );
        $this->agregarRecurso($historialVoleyAdicional, 'Pelota de vóley', $usuario, 3);

        $historialAdmin = $this->crearHistorial(
            $admin,
            $admin,
            'Reunión administrativa',
            'Reunión interna de administración.',
            'Salón de Actos',
            '2026-10-26 10:00:00'
        );
        $this->agregarRecurso($historialAdmin, 'Proyector salón de actos', $admin, 1);
        $this->agregarRecurso($historialAdmin, 'Equipo de sonido', $admin, 1);
    }

    private function userForRole(string $roleName): User
    {
        $role = Rol::where('name', $roleName)->firstOrFail();

        return User::where('role_id', $role->id)->firstOrFail();
    }

    private function crearHistorial(
        User $responsable,
        User $solicitante,
        string $nombre,
        string $descripcion,
        string $espacio,
        string $fecha
    ): Historial {
        return Historial::create([
            'evento_id' => null,
            'responsable_id' => $responsable->id,
            'solicitante_id' => $solicitante->id,
            'evento_nombre' => $nombre,
            'descripcion' => $descripcion,
            'espacio_nombre' => $espacio,
            'fecha_realizacion' => $fecha,
        ]);
    }

    private function agregarRecurso(
        Historial $historial,
        string $recursoNombre,
        User $solicitante,
        int $cantidad
    ): void {
        $recurso = Recurso::where('name', $recursoNombre)->firstOrFail();

        HistorialRecurso::create([
            'historial_id' => $historial->id,
            'recurso_id' => $recurso->id,
            'solicitante_id' => $solicitante->id,
            'recurso_nombre' => $recurso->name,
            'cantidad' => $cantidad,
        ]);
    }
}
