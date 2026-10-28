<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const RESOURCE_GROUPS = [
        'Proyector salón de actos' => ['Proyector Salon de Actos'],
        'Equipo de sonido' => ['Equipo de Sonido SUM'],
        'Pelota de vóley' => ['Pelota Voley 1', 'Pelota Voley 2', 'Pelota Voley 3', 'Pelota Voley 4'],
        'Pelota de fútbol' => ['Pelota Futbol 1', 'Pelota Futbol 2'],
        'Pelota de futsal' => ['Pelota Futsal 1', 'Pelota Futsal 2'],
        'Guitarra' => ['Guitarra 1', 'Guitarra 2'],
        'Piano' => ['Piano 1', 'Piano 2'],
        'Cajón peruano' => ['Cajon Peruano'],
    ];

    public function up(): void
    {
        if (!Schema::hasTable('recursos') || !Schema::hasTable('evento_recurso')) {
            return;
        }

        DB::transaction(function () {
            foreach (self::RESOURCE_GROUPS as $canonicalName => $legacyNames) {
                $rows = DB::table('recursos')
                    ->whereIn('name', array_merge([$canonicalName], $legacyNames))
                    ->orderBy('id')
                    ->get();

                if ($rows->isEmpty()) {
                    continue;
                }

                $resourceIds = $rows->pluck('id')->map(fn ($id) => (int) $id)->all();
                $primaryId = (int) $resourceIds[0];
                $total = $rows->sum(fn ($row) => max(1, (int) $row->cantidad_total));

                $this->mergeEventReservations($resourceIds, $primaryId);
                $this->mergeHistoryResources($resourceIds, $primaryId, $canonicalName);

                if (Schema::hasTable('eventos') && Schema::hasColumn('eventos', 'recurso_id')) {
                    DB::table('eventos')
                        ->whereIn('recurso_id', $resourceIds)
                        ->update(['recurso_id' => $primaryId]);
                }

                $activeReservations = DB::table('evento_recurso')
                    ->join('eventos', 'eventos.id', '=', 'evento_recurso.evento_id')
                    ->where('evento_recurso.recurso_id', $primaryId)
                    ->where('eventos.estado', 'programado')
                    ->sum('evento_recurso.cantidad');
                $total = max($total, (int) $activeReservations);

                DB::table('recursos')
                    ->where('id', $primaryId)
                    ->update([
                        'name' => $canonicalName,
                        'cantidad_total' => $total,
                        'cantidad_disponible' => $total - (int) $activeReservations,
                    ]);

                if (count($resourceIds) > 1) {
                    DB::table('recursos')->whereIn('id', array_slice($resourceIds, 1))->delete();
                }
            }

            $this->normalizeUnclassifiedResources();
        });
    }

    private function mergeEventReservations(array $resourceIds, int $primaryId): void
    {
        $reservations = DB::table('evento_recurso')
            ->whereIn('recurso_id', $resourceIds)
            ->get()
            ->groupBy('evento_id');

        foreach ($reservations as $eventoId => $items) {
            $cantidad = $items->sum('cantidad');

            DB::table('evento_recurso')
                ->where('evento_id', $eventoId)
                ->whereIn('recurso_id', $resourceIds)
                ->delete();

            DB::table('evento_recurso')->insert([
                'evento_id' => $eventoId,
                'recurso_id' => $primaryId,
                'cantidad' => $cantidad,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function mergeHistoryResources(array $resourceIds, int $primaryId, string $canonicalName): void
    {
        if (!Schema::hasTable('historial_recursos')) {
            return;
        }

        DB::table('historial_recursos')
            ->whereIn('recurso_id', $resourceIds)
            ->update([
                'recurso_id' => $primaryId,
                'recurso_nombre' => $canonicalName,
            ]);

        $historyRows = DB::table('historial_recursos')
            ->where('recurso_id', $primaryId)
            ->get()
            ->groupBy(fn ($row) => $row->historial_id.'-'.$row->solicitante_id);

        foreach ($historyRows as $items) {
            if ($items->count() < 2) {
                continue;
            }

            $keepId = $items->first()->id;
            DB::table('historial_recursos')
                ->where('id', $keepId)
                ->update(['cantidad' => $items->sum('cantidad')]);
            DB::table('historial_recursos')
                ->whereIn('id', $items->skip(1)->pluck('id'))
                ->delete();
        }
    }

    private function normalizeUnclassifiedResources(): void
    {
        $resources = DB::table('recursos')
            ->leftJoin('evento_recurso', 'evento_recurso.recurso_id', '=', 'recursos.id')
            ->leftJoin('eventos', function ($join) {
                $join->on('eventos.id', '=', 'evento_recurso.evento_id')
                    ->where('eventos.estado', '=', 'programado');
            })
            ->groupBy('recursos.id', 'recursos.cantidad_total')
            ->select(
                'recursos.id',
                'recursos.cantidad_total',
                DB::raw('COALESCE(SUM(CASE WHEN eventos.id IS NOT NULL THEN evento_recurso.cantidad ELSE 0 END), 0) as reservadas')
            )
            ->get();

        foreach ($resources as $resource) {
            $total = max(1, (int) $resource->cantidad_total, (int) $resource->reservadas);

            DB::table('recursos')
                ->where('id', $resource->id)
                ->update([
                    'cantidad_total' => $total,
                    'cantidad_disponible' => $total - (int) $resource->reservadas,
                ]);
        }
    }

    public function down(): void
    {
        throw new RuntimeException('Resource unit consolidation is irreversible; restore a database backup to undo it.');
    }
};
