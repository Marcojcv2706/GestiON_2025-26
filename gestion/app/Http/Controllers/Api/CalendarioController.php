<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Evento;
use App\Models\Historial;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CalendarioController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $inicio = $request->query('start');
        $fin = $request->query('end');

        $eventos = Evento::with([
            'espacio',
            'subEspacio.espacio',
            'subEspacios',
            'recursos',
            'user',
        ])
            ->when($inicio, function ($query) use ($inicio) {
                $query->where('end_time', '>', $inicio);
            })
            ->when($fin, function ($query) use ($fin) {
                $query->where('start_time', '<', $fin);
            })
            ->where('estado', 'programado')
            ->get();

        $eventosMapeados = $eventos->map(function ($evento) {
            $subspaceNames = $evento->subEspacios->pluck('name');
            if ($subspaceNames->isEmpty() && $evento->subEspacio) {
                $subspaceNames = collect([$evento->subEspacio->name]);
            }
            $espacio = $evento->espacio?->name
                ?? $evento->subEspacio?->espacio?->name
                ?? 'No especificado';
            if ($subspaceNames->isNotEmpty()) {
                $espacio .= ' · '.$subspaceNames->implode(' + ');
            }

            return [
                'id' => 'evento-' . $evento->id,
                'title' => $evento->name ?? 'Evento sin nombre',
                'start' => $evento->start_time->format('Y-m-d\TH:i:s'),
                'end' => $evento->end_time->format('Y-m-d\TH:i:s'),
                'allDay' => false,
                'extendedProps' => [
                    'tipo' => 'Evento',
                    'espacio' => $espacio,
                    'subespacios' => $subspaceNames->values(),
                    'descripcion' => $evento->description,
                    'responsable' => $evento->user?->name ?? 'No especificado',
                    'recursos' => $evento->recursos
                        ->map(fn ($recurso) => "{$recurso->name} (×{$recurso->pivot->cantidad})")
                        ->values(),
                ],
            ];
        });

        $historiales = Historial::with(['responsable'])
            ->when($inicio, function ($query) use ($inicio) {
                $query->where('fecha_realizacion', '>=', $inicio); 
            })
            ->when($fin, function ($query) use ($fin) {
                $query->where('fecha_realizacion', '<=', $fin);
            })
            ->get();

        $eventosHistorial = $historiales->map(function ($historial) {
            return [
                'id' => 'historial-' . $historial->id,
                'title' => $historial->evento_nombre ?? 'Sin nombre', // Usando el nuevo nombre de la columna
                'start' => $historial->fecha_realizacion ? $historial->fecha_realizacion->format('Y-m-d\TH:i:s') : null, 
                'end' => $historial->fecha_realizacion ? $historial->fecha_realizacion->format('Y-m-d\TH:i:s') : null, 
                'allDay' => false, 
                'extendedProps' => [
                    'tipo' => 'Historial',
                    'espacio' => $historial->espacio_nombre ?? 'No especificado',
                    'descripcion' => $historial->descripcion,
                    'responsable' => $historial->responsable?->name ?? 'No especificado',
                    'recurso' => null, 
                ],
            ];
        });

        $todosLosEventos = $eventosMapeados->concat($eventosHistorial);

        return response()->json($todosLosEventos);
    }
}