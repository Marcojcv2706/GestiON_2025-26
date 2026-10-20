<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Turno;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CalendarioController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $inicio = $request->query('start');
        $fin = $request->query('end');

        $turnos = Turno::with([
            'actividad.subEspacio.espacio',
            'recurso',
            'espacio',
        ])
            ->when($inicio, fn ($query) =>
                $query->where('end_time', '>', $inicio)
            )
            ->when($fin, fn ($query) =>
                $query->where('start_time', '<', $fin)
            )
            ->whereNull('deleted_at')
            ->get();

        $eventos = $turnos->map(function ($turno) {
            $actividad = $turno->actividad;

            $espacio = $turno->espacio?->name
                ?? $actividad?->subEspacio?->espacio?->name;

            return [
                'id' => 'turno-' . $turno->id,
                'title' => $actividad?->name
                    ?? $turno->recurso?->name
                    ?? $turno->description
                    ?? 'Turno reservado',
                'start' => $turno->start_time,
                'end' => $turno->end_time,
                'allDay' => false,
                'extendedProps' => [
                    'tipo' => $actividad ? 'actividad' : 'turno',
                    'espacio' => $espacio,
                    'descripcion' => $actividad?->description
                        ?? $turno->description,
                ],
            ];
        })->values();

        return response()->json($eventos);
    }
}
