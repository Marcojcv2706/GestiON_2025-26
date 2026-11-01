<?php

namespace App\Http\Controllers;

use App\Models\SolicitudEvento;
use App\Models\SolicitudRecurso;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SolicitudController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $isAdmin = $user->can('es-admin');
        $isTeacher = $user->can('es-profesor');
        abort_unless($isAdmin || $isTeacher, 403);

        $validated = $request->validate([
            'tipo' => ['nullable', Rule::in(['todas', 'eventos', 'implementos'])],
            'estado' => ['nullable', Rule::in([
                SolicitudEvento::PENDIENTE,
                SolicitudEvento::APROBADA,
                SolicitudEvento::RECHAZADA,
                SolicitudEvento::MODIFICACION_SOLICITADA,
                SolicitudRecurso::DEVUELTA,
            ])],
        ]);

        $type = $isAdmin ? ($validated['tipo'] ?? 'todas') : 'implementos';
        $status = $validated['estado'] ?? '';
        $queries = [];

        if ($isAdmin && $type !== 'implementos') {
            $events = DB::table('solicitudes_eventos')
                ->selectRaw("'evento' as tipo, id, user_id, estado, created_at, name as titulo, description, start_time, end_time, espacio_id, sub_espacio_id, NULL as fecha_uso, NULL as motivo, observacion_admin, revisado_por, decidido_en");
            if ($status !== '') {
                $events->where('estado', $status);
            }
            $queries[] = $events;
        }

        if ($type !== 'eventos') {
            $resources = DB::table('solicitudes_recursos')
                ->selectRaw("'implemento' as tipo, id, user_id, estado, created_at, NULL as titulo, NULL as description, NULL as start_time, NULL as end_time, NULL as espacio_id, NULL as sub_espacio_id, fecha_uso, motivo, observacion_admin, revisado_por, decidido_en");

            if ($isTeacher) {
                $resources->where(function (Builder $query) use ($user) {
                    $query->where('estado', SolicitudRecurso::PENDIENTE)
                        ->orWhere('revisado_por', $user->id);
                });
            }
            if ($status !== '') {
                $resources->where('estado', $status);
            }
            $queries[] = $resources;
        }

        $union = array_shift($queries) ?? DB::table('solicitudes_eventos')
            ->selectRaw("'evento' as tipo, id, user_id, estado, created_at, name as titulo, description, start_time, end_time, espacio_id, sub_espacio_id, NULL as fecha_uso, NULL as motivo, observacion_admin, revisado_por, decidido_en")
            ->whereRaw('1 = 0');
        foreach ($queries as $query) {
            $union->unionAll($query);
        }

        $page = DB::query()
            ->fromSub($union, 'solicitudes')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        $eventIds = $page->getCollection()->where('tipo', 'evento')->pluck('id');
        $resourceIds = $page->getCollection()->where('tipo', 'implemento')->pluck('id');
        $eventRequests = $eventIds->isEmpty()
            ? collect()
            : SolicitudEvento::with(['user', 'espacio', 'subEspacio', 'subEspacios'])
                ->whereKey($eventIds)
                ->get()
                ->keyBy('id');
        $resourceRequests = $resourceIds->isEmpty()
            ? collect()
            : SolicitudRecurso::with(['user', 'recursos'])
                ->whereKey($resourceIds)
                ->get()
                ->keyBy('id');

        $page->getCollection()->transform(function (object $row) use ($eventRequests, $resourceRequests) {
            $row->solicitud = $row->tipo === 'evento'
                ? $eventRequests->get($row->id)
                : $resourceRequests->get($row->id);

            return $row;
        });

        return view('solicitudes.index', [
            'solicitudes' => $page,
            'isAdmin' => $isAdmin,
            'isTeacher' => $isTeacher,
            'canReviewResources' => $isAdmin || $isTeacher,
            'tipo' => $type,
            'estado' => $status,
        ]);
    }
}
