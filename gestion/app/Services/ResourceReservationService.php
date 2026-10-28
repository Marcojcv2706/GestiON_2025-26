<?php

namespace App\Services;

use App\Models\Evento;
use App\Models\Recurso;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class ResourceReservationService
{
    public function sync(Evento $evento, array $requestedQuantities, bool $approval = false): void
    {
        $existing = $evento->recursos()->get()->keyBy('id');
        $resourceIds = collect($existing->keys())
            ->merge(array_keys($requestedQuantities))
            ->unique()
            ->sort()
            ->values();

        $resources = $this->lockResources($resourceIds);

        foreach ($requestedQuantities as $resourceId => $quantity) {
            if (!$resources->has($resourceId)) {
                throw ValidationException::withMessages([
                    'recursos' => 'Uno de los recursos seleccionados ya no está disponible.',
                ]);
            }

            $resource = $resources->get($resourceId);
            if ($resource->cantidad_disponible < 0 || $resource->cantidad_disponible > $resource->cantidad_total) {
                throw ValidationException::withMessages([
                    'recursos' => "El inventario de {$resource->name} tiene cantidades inconsistentes. Corrige el total antes de reservar.",
                ]);
            }

            $previousQuantity = (int) ($existing->get($resourceId)?->pivot->cantidad ?? 0);
            $availableForEvent = (int) $resource->cantidad_disponible + $previousQuantity;

            if ($quantity > $availableForEvent) {
                $message = $approval
                    ? "No es posible aprobar la solicitud: {$resource->name} requiere {$quantity} unidades y actualmente solo hay {$availableForEvent} disponibles."
                    : "No hay suficientes unidades de {$resource->name}. Cantidad disponible: {$availableForEvent}.";
                throw ValidationException::withMessages([
                    'recursos' => $message,
                ]);
            }
        }

        $syncData = [];

        foreach ($resourceIds as $resourceId) {
            $resource = $resources->get($resourceId);
            $previousQuantity = (int) ($existing->get($resourceId)?->pivot->cantidad ?? 0);
            $newQuantity = (int) ($requestedQuantities[$resourceId] ?? 0);
            $resource->cantidad_disponible += $previousQuantity - $newQuantity;

            if ($resource->cantidad_disponible < 0 || $resource->cantidad_disponible > $resource->cantidad_total) {
                throw ValidationException::withMessages([
                    'recursos' => "No se pudo actualizar la disponibilidad de {$resource->name}. Revisa el inventario.",
                ]);
            }

            $resource->save();

            if ($newQuantity > 0) {
                $syncData[$resourceId] = ['cantidad' => $newQuantity];
            }
        }

        $evento->recursos()->sync($syncData);
        $evento->recurso_id = array_key_first($syncData);
        $evento->save();
    }

    public function release(Evento $evento): void
    {
        $reservations = $evento->recursos()->get()->keyBy('id');
        $resources = $this->lockResources($reservations->keys()->sort()->values());

        foreach ($reservations as $resourceId => $reservation) {
            $resource = $resources->get($resourceId);
            if (!$resource) {
                continue;
            }

            $resource->cantidad_disponible = min(
                (int) $resource->cantidad_total,
                (int) $resource->cantidad_disponible + (int) $reservation->pivot->cantidad
            );
            $resource->save();
        }
    }

    private function lockResources(Collection $resourceIds): Collection
    {
        if ($resourceIds->isEmpty()) {
            return collect();
        }

        return Recurso::query()
            ->whereIn('id', $resourceIds)
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->keyBy('id');
    }
}
