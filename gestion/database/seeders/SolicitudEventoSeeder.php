<?php

namespace Database\Seeders;

use App\Models\Espacio;
use App\Models\Evento;
use App\Models\Recurso;
use App\Models\Rol;
use App\Models\SolicitudEvento;
use App\Models\SolicitudEventoDecision;
use App\Models\SubEspacio;
use App\Models\User;
use App\Services\ResourceReservationService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SolicitudEventoSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('role_id', Rol::where('name', 'Administrador')->firstOrFail()->id)->firstOrFail();
        $profesor = User::where('role_id', Rol::where('name', 'Profesor')->firstOrFail()->id)->firstOrFail();
        $usuario = User::where('role_id', Rol::where('name', 'Usuario')->firstOrFail()->id)->firstOrFail();
        $subEspacio = SubEspacio::where('name', 'Cancha Futbol 5')->firstOrFail();

        $this->createRequest(
            $profesor,
            $admin,
            $subEspacio,
            'Torneo institucional',
            2,
            SolicitudEvento::PENDIENTE,
            null,
            ['Pelota de fútbol' => 1]
        );
        $this->createRequest(
            $usuario,
            $admin,
            $subEspacio,
            'Encuentro deportivo estudiantil',
            4,
            SolicitudEvento::APROBADA,
            null,
            ['Pelota de fútbol' => 1]
        );
        $this->createRequest(
            $profesor,
            $admin,
            $subEspacio,
            'Práctica de futsal',
            6,
            SolicitudEvento::RECHAZADA,
            'El espacio ya se encuentra reservado en el horario solicitado.',
            []
        );
        $this->createRequest(
            $usuario,
            $admin,
            $subEspacio,
            'Torneo de cursos',
            8,
            SolicitudEvento::MODIFICACION_SOLICITADA,
            'Indica un horario alternativo para coordinar el uso del espacio.',
            ['Pelota de fútbol' => 1]
        );
    }

    private function createRequest(
        User $user,
        User $admin,
        SubEspacio $subEspacio,
        string $name,
        int $daysAhead,
        string $state,
        ?string $reason,
        array $resources
    ): void {
        $start = now()->addDays($daysAhead)->setTime(10, 0);
        $solicitud = SolicitudEvento::firstOrCreate(
            ['user_id' => $user->id, 'name' => $name],
            [
                'espacio_id' => $subEspacio->espacio_id,
                'sub_espacio_id' => $subEspacio->id,
                'description' => 'Solicitud de muestra para probar el flujo de revisión.',
                'start_time' => $start,
                'end_time' => $start->copy()->addHours(2),
                'estado' => SolicitudEvento::PENDIENTE,
            ]
        );

        if ($solicitud->wasRecentlyCreated) {
            $pivot = [];
            foreach ($resources as $resourceName => $quantity) {
                $resource = Recurso::where('name', $resourceName)->firstOrFail();
                $pivot[$resource->id] = ['cantidad' => $quantity];
            }
            $solicitud->recursos()->sync($pivot);
            SolicitudEventoDecision::create([
                'solicitud_evento_id' => $solicitud->id,
                'actor_id' => $user->id,
                'accion' => 'solicitud_enviada',
                'estado_nuevo' => SolicitudEvento::PENDIENTE,
            ]);
        }

        if ($state === SolicitudEvento::PENDIENTE || !$solicitud->wasRecentlyCreated) {
            return;
        }

        DB::transaction(function () use ($solicitud, $user, $admin, $state, $reason) {
            $solicitud->update([
                'estado' => $state,
                'revisado_por' => $admin->id,
                'observacion_admin' => $reason,
                'decidido_en' => now(),
            ]);

            if ($state === SolicitudEvento::APROBADA) {
                $evento = Evento::create([
                    'name' => $solicitud->name,
                    'description' => $solicitud->description,
                    'start_time' => $solicitud->start_time,
                    'end_time' => $solicitud->end_time,
                    'espacio_id' => $solicitud->espacio_id,
                    'sub_espacio_id' => $solicitud->sub_espacio_id,
                    'user_id' => $user->id,
                    'estado' => 'programado',
                ]);
                app(ResourceReservationService::class)->sync(
                    $evento,
                    $solicitud->recursos()->get()->mapWithKeys(
                        fn (Recurso $resource) => [$resource->id => (int) $resource->pivot->cantidad]
                    )->all()
                );
                $solicitud->update(['evento_id' => $evento->id]);
            }

            SolicitudEventoDecision::create([
                'solicitud_evento_id' => $solicitud->id,
                'actor_id' => $admin->id,
                'accion' => $state,
                'estado_anterior' => SolicitudEvento::PENDIENTE,
                'estado_nuevo' => $state,
                'motivo' => $reason,
            ]);
        });
    }
}
