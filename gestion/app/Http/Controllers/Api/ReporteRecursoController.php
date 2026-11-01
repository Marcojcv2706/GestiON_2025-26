<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Espacio;
use App\Models\Recurso;
use App\Models\SubEspacio;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReporteRecursoController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'desde' => ['nullable', 'date'],
            'hasta' => ['nullable', 'date', 'after_or_equal:desde'],
            'espacio_id' => ['nullable', 'integer', 'exists:espacios,id'],
            'subespacio_id' => ['nullable', 'integer', 'exists:sub_espacios,id'],
            'categoria' => ['nullable', 'string', 'in:'.implode(',', Recurso::CATEGORIAS)],
            'recurso_id' => ['nullable', 'integer', 'exists:recursos,id'],
            'usuario_id' => ['nullable', 'integer', 'exists:users,id'],
            'periodo' => ['nullable', 'in:dia,semana,mes'],
        ]);
        $validated['periodo'] = $validated['periodo'] ?? 'mes';

        $history = $this->resourceHistoryQuery($validated);

        return response()->json([
            'filtros' => $validated,
            'opciones' => $this->filterOptions(),
            'estadisticas' => [
                'espacios' => $this->spaceStatistics(clone $history),
                'subespacios' => $this->subspaceStatistics(clone $history),
                'implementos' => $this->resourceStatistics(clone $history),
                'usuarios' => $this->userStatistics(clone $history),
                'categorias' => $this->categoryStatistics(clone $history),
                'periodos' => $this->periodStatistics(clone $history, $validated['periodo']),
            ],
        ]);
    }

    private function resourceHistoryQuery(array $filters): Builder
    {
        $query = DB::table('historial_recursos as hr')
            ->join('historials as h', 'h.id', '=', 'hr.historial_id')
            ->join('recursos as r', 'r.id', '=', 'hr.recurso_id');

        if (!empty($filters['desde'])) {
            $query->where('h.fecha_realizacion', '>=', $filters['desde'].' 00:00:00');
        }

        if (!empty($filters['hasta'])) {
            $query->where('h.fecha_realizacion', '<', Carbon::parse($filters['hasta'])->addDay()->toDateString().' 00:00:00');
        }

        if (!empty($filters['espacio_id'])) {
            $space = Espacio::findOrFail($filters['espacio_id']);
            $spacePrefix = strtr($space->name, ['!' => '!!', '%' => '!%', '_' => '!_']).' ·%';

            $query->where(function (Builder $records) use ($space, $spacePrefix) {
                $records->where('h.espacio_nombre', $space->name)
                    ->orWhereRaw("h.espacio_nombre LIKE ? ESCAPE '!'", [$spacePrefix])
                    ->orWhereExists(function (Builder $subspaces) use ($space) {
                        $subspaces->selectRaw('1')
                            ->from('sub_espacios as ss')
                            ->whereColumn('ss.name', 'h.espacio_nombre')
                            ->where('ss.espacio_id', $space->id);
                    })
                    ->orWhereExists(function (Builder $events) use ($space) {
                        $events->selectRaw('1')
                            ->from('eventos as e')
                            ->whereColumn('e.id', 'h.evento_id')
                            ->where(function (Builder $eventSpace) use ($space) {
                                $eventSpace->where('e.espacio_id', $space->id)
                                    ->orWhereExists(function (Builder $eventSubspaces) use ($space) {
                                        $eventSubspaces->selectRaw('1')
                                            ->from('evento_sub_espacio as es')
                                            ->join('sub_espacios as ss', 'ss.id', '=', 'es.sub_espacio_id')
                                            ->whereColumn('es.evento_id', 'e.id')
                                            ->where('ss.espacio_id', $space->id);
                                    });
                            });
                    });
            });
        }

        if (!empty($filters['subespacio_id'])) {
            $subspaceId = (int) $filters['subespacio_id'];
            $query->where(function (Builder $records) use ($subspaceId) {
                $records->where('h.espacio_nombre', SubEspacio::query()->whereKey($subspaceId)->value('name'))
                    ->orWhereExists(function (Builder $events) use ($subspaceId) {
                        $events->selectRaw('1')
                            ->from('eventos as e')
                            ->whereColumn('e.id', 'h.evento_id')
                            ->where(function (Builder $eventSubspaces) use ($subspaceId) {
                                $eventSubspaces->where('e.sub_espacio_id', $subspaceId)
                                    ->orWhereExists(function (Builder $pivot) use ($subspaceId) {
                                        $pivot->selectRaw('1')
                                            ->from('evento_sub_espacio as es')
                                            ->whereColumn('es.evento_id', 'e.id')
                                            ->where('es.sub_espacio_id', $subspaceId);
                                    });
                            });
                    });
            });
        }

        if (!empty($filters['categoria'])) {
            $query->where('r.tipo', $filters['categoria']);
        }

        if (!empty($filters['recurso_id'])) {
            $query->where('hr.recurso_id', $filters['recurso_id']);
        }

        if (!empty($filters['usuario_id'])) {
            $query->where('hr.solicitante_id', $filters['usuario_id']);
        }

        return $query;
    }

    private function spaceStatistics(Builder $history): array
    {
        $subspaceParents = SubEspacio::query()
            ->with('espacio:id,name')
            ->get(['name', 'espacio_id'])
            ->mapWithKeys(fn (SubEspacio $subspace) => [$subspace->name => $subspace->espacio?->name]);

        $groups = $history
            ->select('h.espacio_nombre')
            ->selectRaw('COUNT(DISTINCT h.id) as usos')
            ->selectRaw('COUNT(hr.id) as solicitudes')
            ->selectRaw('COALESCE(SUM(hr.cantidad), 0) as unidades')
            ->groupBy('h.espacio_nombre')
            ->get();

        $statistics = [];
        foreach ($groups as $group) {
            $snapshot = trim((string) $group->espacio_nombre);
            $name = $subspaceParents->get($snapshot)
                ?? trim(explode(' · ', $snapshot, 2)[0])
                ?: 'Espacio no especificado';
            $statistics[$name] ??= ['nombre' => $name, 'usos' => 0, 'solicitudes' => 0, 'unidades' => 0];
            $statistics[$name]['usos'] += (int) $group->usos;
            $statistics[$name]['solicitudes'] += (int) $group->solicitudes;
            $statistics[$name]['unidades'] += (int) $group->unidades;
        }

        $statistics = array_values($statistics);
        usort($statistics, fn (array $a, array $b) => ($b['usos'] <=> $a['usos']) ?: ($a['nombre'] <=> $b['nombre']));

        return $statistics;
    }

    private function subspaceStatistics(Builder $history): array
    {
        $linked = (clone $history)
            ->leftJoin('eventos as e', 'e.id', '=', 'h.evento_id')
            ->leftJoin('evento_sub_espacio as es', 'es.evento_id', '=', 'e.id')
            ->leftJoin('sub_espacios as ss', function ($join) {
                $join->on('ss.id', '=', DB::raw('COALESCE(es.sub_espacio_id, e.sub_espacio_id)'));
            })
            ->leftJoin('espacios as s', 's.id', '=', 'ss.espacio_id')
            ->whereNotNull('ss.id')
            ->select('ss.id', 'ss.name as nombre', 's.name as espacio')
            ->selectRaw('COUNT(DISTINCT h.id) as usos')
            ->selectRaw('COUNT(DISTINCT hr.id) as solicitudes')
            ->selectRaw('COALESCE(SUM(hr.cantidad), 0) as unidades')
            ->groupBy('ss.id', 'ss.name', 's.name')
            ->get();

        $snapshots = (clone $history)
            ->join('sub_espacios as ss', 'ss.name', '=', 'h.espacio_nombre')
            ->join('espacios as s', 's.id', '=', 'ss.espacio_id')
            ->whereNull('h.evento_id')
            ->select('ss.id', 'ss.name as nombre', 's.name as espacio')
            ->selectRaw('COUNT(DISTINCT h.id) as usos')
            ->selectRaw('COUNT(DISTINCT hr.id) as solicitudes')
            ->selectRaw('COALESCE(SUM(hr.cantidad), 0) as unidades')
            ->groupBy('ss.id', 'ss.name', 's.name')
            ->get();

        $statistics = [];
        foreach ($linked->concat($snapshots) as $row) {
            $id = (int) $row->id;
            $statistics[$id] ??= [
                'nombre' => $row->nombre,
                'espacio' => $row->espacio,
                'usos' => 0,
                'solicitudes' => 0,
                'unidades' => 0,
            ];
            $statistics[$id]['usos'] += (int) $row->usos;
            $statistics[$id]['solicitudes'] += (int) $row->solicitudes;
            $statistics[$id]['unidades'] += (int) $row->unidades;
        }

        $statistics = array_values($statistics);
        usort($statistics, fn (array $a, array $b) => ($b['usos'] <=> $a['usos']) ?: ($a['nombre'] <=> $b['nombre']));

        return $statistics;
    }

    private function resourceStatistics(Builder $history): array
    {
        return $history
            ->select('hr.recurso_id', 'hr.recurso_nombre', 'r.tipo as categoria')
            ->selectRaw('COUNT(hr.id) as solicitudes')
            ->selectRaw('COALESCE(SUM(hr.cantidad), 0) as unidades')
            ->groupBy('hr.recurso_id', 'hr.recurso_nombre', 'r.tipo')
            ->orderByDesc('solicitudes')
            ->orderBy('hr.recurso_nombre')
            ->get()
            ->map(fn ($row) => [
                'id' => (int) $row->recurso_id,
                'nombre' => $row->recurso_nombre,
                'categoria' => $row->categoria,
                'solicitudes' => (int) $row->solicitudes,
                'unidades' => (int) $row->unidades,
            ])
            ->all();
    }

    private function userStatistics(Builder $history): array
    {
        return $history
            ->join('users as u', 'u.id', '=', 'hr.solicitante_id')
            ->select('u.id', 'u.name as nombre')
            ->selectRaw('COUNT(hr.id) as solicitudes')
            ->selectRaw('COALESCE(SUM(hr.cantidad), 0) as unidades')
            ->groupBy('u.id', 'u.name')
            ->orderByDesc('solicitudes')
            ->orderBy('u.name')
            ->get()
            ->map(fn ($row) => [
                'id' => (int) $row->id,
                'nombre' => $row->nombre,
                'solicitudes' => (int) $row->solicitudes,
                'unidades' => (int) $row->unidades,
            ])
            ->all();
    }

    private function categoryStatistics(Builder $history): array
    {
        $counts = $history
            ->whereIn('r.tipo', Recurso::CATEGORIAS)
            ->select('r.tipo as categoria')
            ->selectRaw('COUNT(hr.id) as solicitudes')
            ->selectRaw('COALESCE(SUM(hr.cantidad), 0) as unidades')
            ->groupBy('r.tipo')
            ->get()
            ->keyBy('categoria');

        return collect(Recurso::CATEGORIAS)
            ->map(fn (string $category) => [
                'categoria' => $category,
                'solicitudes' => (int) ($counts->get($category)->solicitudes ?? 0),
                'unidades' => (int) ($counts->get($category)->unidades ?? 0),
            ])
            ->all();
    }

    private function periodStatistics(Builder $history, string $period): array
    {
        $bucket = $this->periodExpression($period);

        return $history
            ->whereNotNull('h.fecha_realizacion')
            ->selectRaw("{$bucket} as periodo")
            ->selectRaw('COUNT(hr.id) as solicitudes')
            ->selectRaw('COALESCE(SUM(hr.cantidad), 0) as unidades')
            ->groupByRaw($bucket)
            ->orderBy('periodo')
            ->get()
            ->map(fn ($row) => [
                'periodo' => (string) $row->periodo,
                'solicitudes' => (int) $row->solicitudes,
                'unidades' => (int) $row->unidades,
            ])
            ->all();
    }

    private function periodExpression(string $period): string
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            return match ($period) {
                'dia' => 'date(h.fecha_realizacion)',
                'semana' => "date(h.fecha_realizacion, '-' || ((CAST(strftime('%w', h.fecha_realizacion) AS INTEGER) + 6) % 7) || ' days')",
                default => "strftime('%Y-%m-01', h.fecha_realizacion)",
            };
        }

        if ($driver === 'pgsql') {
            return match ($period) {
                'dia' => 'DATE(h.fecha_realizacion)',
                'semana' => "DATE_TRUNC('week', h.fecha_realizacion)::date",
                default => "DATE_TRUNC('month', h.fecha_realizacion)::date",
            };
        }

        return match ($period) {
            'dia' => 'DATE(h.fecha_realizacion)',
            'semana' => 'DATE_SUB(DATE(h.fecha_realizacion), INTERVAL WEEKDAY(h.fecha_realizacion) DAY)',
            default => "DATE_FORMAT(h.fecha_realizacion, '%Y-%m-01')",
        };
    }

    private function filterOptions(): array
    {
        $resourceIds = DB::table('historial_recursos')->select('recurso_id')->distinct();
        $userIds = DB::table('historial_recursos')->select('solicitante_id')->distinct();

        return [
            'espacios' => Espacio::query()->orderBy('name')->get(['id', 'name']),
            'subespacios' => SubEspacio::query()
                ->with('espacio:id,name')
                ->orderBy('name')
                ->get(['id', 'name', 'espacio_id']),
            'recursos' => Recurso::query()
                ->whereIn('id', $resourceIds)
                ->orderBy('name')
                ->get(['id', 'name']),
            'usuarios' => User::query()
                ->whereIn('id', $userIds)
                ->orderBy('name')
                ->get(['id', 'name']),
        ];
    }
}
