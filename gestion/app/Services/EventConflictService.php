<?php

namespace App\Services;

use App\Models\Evento;
use App\Models\SubEspacio;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class EventConflictService
{
    public function ensureSubspacesAvailable(
        array $subspaceIds,
        int $spaceId,
        mixed $startTime,
        mixed $endTime,
        ?int $ignoreEventId = null
    ): Collection {
        $subspaceIds = array_values(array_unique(array_map('intval', $subspaceIds)));
        if ($subspaceIds === []) {
            return collect();
        }

        $subspaces = SubEspacio::query()
            ->whereIn('id', $subspaceIds)
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        if ($subspaces->count() !== count(array_unique($subspaceIds))
            || $subspaces->contains(fn ($subspace) => (int) $subspace->espacio_id !== $spaceId)) {
            throw ValidationException::withMessages([
                'sub_espacios' => 'Selecciona únicamente subespacios que pertenezcan al espacio elegido.',
            ]);
        }

        $conflicts = Evento::query()
            ->with('subEspacios')
            ->where('estado', 'programado')
            ->where('start_time', '<', $endTime)
            ->where('end_time', '>', $startTime)
            ->when($ignoreEventId, fn ($query) => $query->where('id', '!=', $ignoreEventId))
            ->where(function ($query) use ($subspaceIds) {
                $query->whereHas('subEspacios', fn ($subspaces) => $subspaces->whereIn('sub_espacios.id', $subspaceIds))
                    ->orWhereIn('sub_espacio_id', $subspaceIds);
            })
            ->get();

        if ($conflicts->isNotEmpty()) {
            $conflictingSubspace = $conflicts
                ->flatMap(fn ($event) => $event->subEspacios->pluck('id')->push($event->sub_espacio_id))
                ->filter(fn ($id) => in_array((int) $id, $subspaceIds, true))
                ->unique()
                ->map(fn ($id) => $subspaces->firstWhere('id', $id)?->name)
                ->filter()
                ->first();

            throw ValidationException::withMessages([
                'start_time' => $conflictingSubspace
                    ? "El subespacio {$conflictingSubspace} ya está reservado en ese horario."
                    : 'Uno de los subespacios seleccionados ya está reservado en ese horario.',
            ]);
        }

        return $subspaces;
    }
}
