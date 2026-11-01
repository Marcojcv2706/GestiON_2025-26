<?php

namespace App\Http\Controllers;

use App\Models\Recurso;
use App\Models\Historial;
use App\Models\HistorialRecurso;
use App\Models\SolicitudRecurso;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SolicitudRecursoController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        if ($user->can('es-usuario')) {
            $solicitudes = SolicitudRecurso::query()
                ->with('recursos')
                ->where('user_id', $user->id)
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->paginate(10);

            return view('solicitudes-recursos.index', compact('solicitudes'));
        }

        abort_unless($user->can('es-admin') || $user->can('es-profesor'), 403);

        return redirect()->route('solicitudes.index', ['tipo' => 'implementos']);
    }

    public function show(Request $request, SolicitudRecurso $solicitud)
    {
        $user = $request->user();
        $isAdmin = $user->can('es-admin');
        $isTeacher = $user->can('es-profesor');
        $isOwner = (int) $solicitud->user_id === (int) $user->id;
        abort_unless(
            $isAdmin
                || ($user->can('es-usuario') && $isOwner)
                || ($isTeacher && (
                    $solicitud->estado === SolicitudRecurso::PENDIENTE
                    || (int) $solicitud->revisado_por === (int) $user->id
                )),
            403
        );
        $solicitud->load(['user', 'revisor', 'recursos']);

        return view('solicitudes-recursos.show', [
            'solicitud' => $solicitud,
            'isAdmin' => $isAdmin,
            'isOwner' => $isOwner,
            'canReview' => $user->can('gestionar-solicitudes-recursos'),
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
            'recursos.*' => ['nullable', 'integer', 'min:0'],
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
        abort_unless($request->user()->can('gestionar-solicitudes-recursos'), 403);

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

            $history = Historial::create([
                'responsable_id' => $request->user()->id,
                'solicitante_id' => $locked->user_id,
                'evento_nombre' => 'Solicitud de implementos #'.$locked->id,
                'descripcion' => $locked->motivo,
                'fecha_realizacion' => $locked->fecha_uso,
            ]);

            foreach ($locked->recursos as $requestedResource) {
                HistorialRecurso::create([
                    'historial_id' => $history->id,
                    'recurso_id' => $requestedResource->id,
                    'solicitante_id' => $locked->user_id,
                    'recurso_nombre' => $requestedResource->name,
                    'cantidad' => $requestedResource->pivot->cantidad,
                ]);
            }

            $locked->update([
                'estado' => SolicitudRecurso::APROBADA,
                'revisado_por' => $request->user()->id,
                'decidido_en' => now(),
                'observacion_admin' => null,
            ]);
        });

        return redirect()->route('solicitudes-recursos.show', $solicitud)
            ->with('success', 'La solicitud de implementos fue aprobada y los implementos quedaron reservados.');
    }

    public function rechazar(Request $request, SolicitudRecurso $solicitud)
    {
        abort_unless($request->user()->can('gestionar-solicitudes-recursos'), 403);
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

        return redirect()->route('solicitudes-recursos.show', $solicitud)
            ->with('success', 'La solicitud de implementos fue rechazada: '.$validated['observacion_admin']);
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
