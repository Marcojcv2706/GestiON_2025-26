<?php

namespace App\Http\Controllers;

use App\Models\Recurso;
use App\Models\SolicitudRecurso;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SolicitudRecursoController extends Controller
{
    public function index(Request $request)
    {
        $isAdmin = $request->user()->can('es-admin');
        abort_unless($isAdmin || $request->user()->can('es-usuario'), 403);
        $query = SolicitudRecurso::with(['user', 'recursos'])->latest();

        if (!$isAdmin) {
            $query->where('user_id', $request->user()->id);
        }

        return view('solicitudes-recursos.index', [
            'solicitudes' => $query->paginate(12),
            'isAdmin' => $isAdmin,
        ]);
    }

    public function create()
    {
        abort_unless(request()->user()->can('es-usuario'), 403);

        return view('solicitudes-recursos.create', [
            'recursos' => Recurso::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->can('es-usuario'), 403);

        $validated = $request->validate([
            'fecha_uso' => ['required', 'date', 'after_or_equal:today'],
            'motivo' => ['required', 'string', 'max:2000'],
            'recursos' => ['required', 'array'],
            'recursos.*' => ['required', 'integer', 'min:0'],
        ]);

        $quantities = collect($validated['recursos'])
            ->map(fn ($quantity) => (int) $quantity)
            ->filter(fn ($quantity) => $quantity > 0);

        if ($quantities->isEmpty()) {
            throw ValidationException::withMessages([
                'recursos' => 'Selecciona al menos un implemento y una cantidad mayor que cero.',
            ]);
        }

        $requestedResources = Recurso::query()
            ->whereIn('id', $quantities->keys())
            ->get()
            ->keyBy('id');

        if ($requestedResources->count() !== $quantities->count()) {
            throw ValidationException::withMessages([
                'recursos' => 'Uno de los implementos seleccionados ya no está disponible.',
            ]);
        }

        foreach ($quantities as $resourceId => $quantity) {
            $resource = $requestedResources->get($resourceId);
            if ($quantity > $resource->cantidad_disponible) {
                throw ValidationException::withMessages([
                    'recursos' => "Solo hay {$resource->cantidad_disponible} unidades disponibles de {$resource->name}.",
                ]);
            }
        }

        $solicitud = DB::transaction(function () use ($request, $validated, $quantities) {
            $solicitud = SolicitudRecurso::create([
                'user_id' => $request->user()->id,
                'fecha_uso' => $validated['fecha_uso'],
                'motivo' => $validated['motivo'],
                'estado' => SolicitudRecurso::PENDIENTE,
            ]);

            $solicitud->recursos()->sync(
                $quantities->mapWithKeys(fn ($quantity, $resourceId) => [
                    $resourceId => ['cantidad' => $quantity],
                ])->all()
            );

            return $solicitud;
        });

        return redirect()->route('solicitudes-recursos.index')
            ->with('success', 'Tu solicitud de implementos quedó pendiente de aprobación.');
    }

    public function aprobar(Request $request, SolicitudRecurso $solicitud)
    {
        abort_unless($request->user()->can('es-admin'), 403);

        DB::transaction(function () use ($request, $solicitud) {
            $locked = SolicitudRecurso::query()
                ->with('recursos')
                ->lockForUpdate()
                ->findOrFail($solicitud->id);
            $this->ensureState($locked, SolicitudRecurso::PENDIENTE);

            $resourceIds = $locked->recursos->modelKeys();
            sort($resourceIds);
            $resources = Recurso::query()
                ->whereIn('id', $resourceIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            foreach ($locked->recursos as $requestedResource) {
                $resource = $resources->get($requestedResource->id);
                $quantity = (int) $requestedResource->pivot->cantidad;

                if (!$resource || $resource->cantidad_disponible < $quantity) {
                    $available = $resource?->cantidad_disponible ?? 0;
                    throw ValidationException::withMessages([
                        'recursos' => "No se puede aprobar: quedan {$available} unidades de {$requestedResource->name}, pero se solicitaron {$quantity}.",
                    ]);
                }
            }

            foreach ($locked->recursos as $requestedResource) {
                $resource = $resources->get($requestedResource->id);
                $resource->cantidad_disponible -= (int) $requestedResource->pivot->cantidad;
                $resource->save();
            }

            $locked->update([
                'estado' => SolicitudRecurso::APROBADA,
                'revisado_por' => $request->user()->id,
                'decidido_en' => now(),
                'observacion_admin' => null,
            ]);
        });

        return back()->with('success', 'Solicitud aprobada. Los implementos quedaron reservados.');
    }

    public function rechazar(Request $request, SolicitudRecurso $solicitud)
    {
        abort_unless($request->user()->can('es-admin'), 403);
        $validated = $request->validate([
            'observacion_admin' => ['required', 'string', 'max:2000'],
        ]);

        DB::transaction(function () use ($request, $solicitud, $validated) {
            $locked = SolicitudRecurso::query()->lockForUpdate()->findOrFail($solicitud->id);
            $this->ensureState($locked, SolicitudRecurso::PENDIENTE);
            $locked->update([
                'estado' => SolicitudRecurso::RECHAZADA,
                'revisado_por' => $request->user()->id,
                'decidido_en' => now(),
                'observacion_admin' => $validated['observacion_admin'],
            ]);
        });

        return back()->with('success', 'La solicitud fue rechazada y el motivo quedó registrado.');
    }

    public function devolver(Request $request, SolicitudRecurso $solicitud)
    {
        abort_unless($request->user()->can('es-admin'), 403);

        DB::transaction(function () use ($request, $solicitud) {
            $locked = SolicitudRecurso::query()
                ->with('recursos')
                ->lockForUpdate()
                ->findOrFail($solicitud->id);
            $this->ensureState($locked, SolicitudRecurso::APROBADA);

            $resourceIds = $locked->recursos->modelKeys();
            sort($resourceIds);
            $resources = Recurso::query()
                ->whereIn('id', $resourceIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            foreach ($locked->recursos as $requestedResource) {
                $resource = $resources->get($requestedResource->id);
                if (!$resource) {
                    throw ValidationException::withMessages([
                        'recursos' => "No se puede registrar la devolución: {$requestedResource->name} ya no existe.",
                    ]);
                }

                $newAvailable = $resource->cantidad_disponible + (int) $requestedResource->pivot->cantidad;
                if ($newAvailable > $resource->cantidad_total) {
                    throw ValidationException::withMessages([
                        'recursos' => "No se puede registrar la devolución de {$resource->name}: la cantidad disponible supera el inventario total.",
                    ]);
                }
            }

            foreach ($locked->recursos as $requestedResource) {
                $resource = $resources->get($requestedResource->id);
                $resource->cantidad_disponible += (int) $requestedResource->pivot->cantidad;
                $resource->save();
            }

            $locked->update([
                'estado' => SolicitudRecurso::DEVUELTA,
                'revisado_por' => $request->user()->id,
                'devuelto_en' => now(),
            ]);
        });

        return back()->with('success', 'La devolución quedó registrada y las unidades volvieron al inventario.');
    }

    private function ensureState(SolicitudRecurso $solicitud, string $expected): void
    {
        if ($solicitud->estado !== $expected) {
            throw ValidationException::withMessages([
                'solicitud' => 'La solicitud ya fue procesada o cambió de estado. Actualiza la página.',
            ]);
        }
    }
}
