<?php

namespace App\Http\Controllers;

use App\Models\Espacio;
use App\Models\Evento;
use App\Models\Historial;
use App\Models\HistorialRecurso;
use App\Models\Recurso;
use App\Models\SubEspacio;
use App\Services\ResourceReservationService;
use App\Services\EventConflictService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class EventoController extends Controller
{
    public function index()
    {
        $isAdmin = request()->user()->can('es-admin');

        $query = Evento::with(['user', 'espacio', 'subEspacios', 'subEspacio', 'recursos']);

        if ($isAdmin) {
            $query->orderByDesc('start_time');
        } else {
            $query->where('estado', 'programado')
                ->where('start_time', '>=', now())
                ->orderBy('start_time');
        }

        return view('eventos.index', [
            'eventos' => $query->paginate(10),
            'isAdmin' => $isAdmin,
        ]);
    }

    public function create()
    {
        return view('eventos.create', $this->formData());
    }

    public function store(Request $request, ResourceReservationService $inventory, EventConflictService $conflicts)
    {
        $this->normalizeLegacyResourceInput($request);
        $this->normalizeLegacySubspaceInput($request);
        $validated = $this->validateEvento($request);
        $requestedQuantities = $this->requestedQuantities($validated);

        DB::transaction(function () use ($request, $validated, $requestedQuantities, $inventory, $conflicts) {
            $subspaceIds = $validated['sub_espacios'] ?? [];
            $conflicts->ensureSubspacesAvailable(
                $subspaceIds,
                (int) $validated['espacio_id'],
                $validated['start_time'],
                $validated['end_time']
            );
            $evento = Evento::create([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'start_time' => $validated['start_time'],
                'end_time' => $validated['end_time'],
                'espacio_id' => $validated['espacio_id'],
                'sub_espacio_id' => $subspaceIds[0] ?? null,
                'user_id' => $request->user()->id,
                'estado' => 'programado',
            ]);

            $evento->subEspacios()->sync($subspaceIds);
            $inventory->sync($evento, $requestedQuantities);
        });

        return redirect()->route('eventos.index')->with('success', 'Evento creado y recursos reservados.');
    }

    public function edit(Evento $evento)
    {
        if ($evento->estado !== 'programado') {
            return redirect()->route('eventos.index')
                ->with('error', 'Solo se pueden editar eventos programados.');
        }

        $evento->load(['recursos', 'subEspacios']);

        return view('eventos.edit', $this->formData($evento));
    }

    public function update(
        Request $request,
        Evento $evento,
        ResourceReservationService $inventory,
        EventConflictService $conflicts
    )
    {
        $this->normalizeLegacyResourceInput($request);
        $this->normalizeLegacySubspaceInput($request);
        $validated = $this->validateEvento($request);
        $requestedQuantities = $this->requestedQuantities($validated);

        DB::transaction(function () use ($validated, $requestedQuantities, $evento, $inventory, $conflicts) {
            $lockedEvento = Evento::query()->lockForUpdate()->findOrFail($evento->id);

            if ($lockedEvento->estado !== 'programado') {
                throw ValidationException::withMessages([
                    'evento' => 'Solo se pueden editar eventos programados.',
                ]);
            }

            $subspaceIds = $validated['sub_espacios'] ?? [];
            $conflicts->ensureSubspacesAvailable(
                $subspaceIds,
                (int) $validated['espacio_id'],
                $validated['start_time'],
                $validated['end_time'],
                $lockedEvento->id
            );
            $lockedEvento->fill([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'start_time' => $validated['start_time'],
                'end_time' => $validated['end_time'],
                'espacio_id' => $validated['espacio_id'],
                'sub_espacio_id' => $subspaceIds[0] ?? null,
            ]);
            $lockedEvento->save();
            $lockedEvento->subEspacios()->sync($subspaceIds);

            $inventory->sync($lockedEvento, $requestedQuantities);
        });

        return redirect()->route('eventos.index')->with('success', 'Evento actualizado y reservas sincronizadas.');
    }

    public function destroy(Evento $evento, ResourceReservationService $inventory)
    {
        DB::transaction(function () use ($evento, $inventory) {
            $lockedEvento = Evento::query()->lockForUpdate()->findOrFail($evento->id);

            if ($lockedEvento->estado === 'programado') {
                $inventory->release($lockedEvento);
                $lockedEvento->estado = 'cancelado';
                $lockedEvento->save();
            }

            $lockedEvento->delete();
        });

        return redirect()->route('eventos.index')->with('success', 'Evento eliminado; las unidades reservadas volvieron al inventario.');
    }

    public function finalizar(Evento $evento, ResourceReservationService $inventory)
    {
        DB::transaction(function () use ($evento, $inventory) {
            $lockedEvento = Evento::query()
                ->with(['recursos', 'espacio', 'subEspacios', 'subEspacio', 'user'])
                ->lockForUpdate()
                ->findOrFail($evento->id);

            if ($lockedEvento->estado !== 'programado') {
                throw ValidationException::withMessages([
                    'evento' => 'Este evento ya fue finalizado o cancelado.',
                ]);
            }

            if ($lockedEvento->end_time->isFuture()) {
                throw ValidationException::withMessages([
                    'evento' => 'El evento solo se puede finalizar después de su hora de término.',
                ]);
            }

            $subspaceNames = $lockedEvento->subEspacios->pluck('name');
            if ($subspaceNames->isEmpty() && $lockedEvento->subEspacio) {
                $subspaceNames = collect([$lockedEvento->subEspacio->name]);
            }
            $spaceDescription = $lockedEvento->espacio?->name
                ?? $lockedEvento->subEspacio?->espacio?->name
                ?? 'No especificado';
            if ($subspaceNames->isNotEmpty()) {
                $spaceDescription .= ' · '.$subspaceNames->implode(' + ');
            }

            $historial = Historial::create([
                'evento_id' => $lockedEvento->id,
                'responsable_id' => $lockedEvento->user_id,
                'solicitante_id' => $lockedEvento->user_id,
                'evento_nombre' => $lockedEvento->name,
                'descripcion' => $lockedEvento->description,
                'espacio_nombre' => $spaceDescription,
                'fecha_realizacion' => $lockedEvento->end_time,
            ]);

            foreach ($lockedEvento->recursos as $recurso) {
                HistorialRecurso::create([
                    'historial_id' => $historial->id,
                    'recurso_id' => $recurso->id,
                    'solicitante_id' => $lockedEvento->user_id,
                    'recurso_nombre' => $recurso->name,
                    'cantidad' => $recurso->pivot->cantidad,
                ]);
            }

            $inventory->release($lockedEvento);
            $lockedEvento->estado = 'finalizado';
            $lockedEvento->save();
        });

        return redirect()->route('eventos.index')->with('success', 'Evento finalizado. Las unidades volvieron al inventario y se registró el historial.');
    }

    private function formData(?Evento $evento = null): array
    {
        return [
            'evento' => $evento,
            'espacios' => Espacio::orderBy('name')->get(),
            'subEspacios' => SubEspacio::with('espacio')->orderBy('name')->get(),
            'recursos' => Recurso::orderBy('name')->get(),
        ];
    }

    private function normalizeLegacyResourceInput(Request $request): void
    {
        if (!$request->has('recursos') && $request->filled('recurso_id')) {
            $resourceId = (int) $request->input('recurso_id');
            $request->merge([
                'recursos' => [[
                    'id' => $resourceId,
                    'cantidad' => 1,
                ]],
            ]);
        }
    }

    private function validateEvento(Request $request): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:255'],
            'start_time' => ['required', 'date'],
            'end_time' => ['required', 'date', 'after:start_time'],
            'espacio_id' => ['required', 'exists:espacios,id'],
            'sub_espacios' => ['nullable', 'array'],
            'sub_espacios.*' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('sub_espacios', 'id')
                    ->where(fn ($query) => $query->where('espacio_id', $request->input('espacio_id'))),
            ],
            'recursos' => ['nullable', 'array'],
            'recursos.*.id' => ['required', 'integer', 'distinct', 'exists:recursos,id'],
            'recursos.*.cantidad' => ['required', 'integer', 'min:0'],
        ], [
            'sub_espacios.*.exists' => 'Selecciona únicamente subespacios que pertenezcan al espacio elegido.',
            'recursos.*.cantidad.min' => 'La cantidad debe ser 0 o un número positivo.',
        ]);

        if (SubEspacio::where('espacio_id', $validated['espacio_id'])->exists()
            && empty($validated['sub_espacios'])) {
            throw ValidationException::withMessages([
                'sub_espacios' => 'Selecciona al menos un subespacio para este espacio.',
            ]);
        }

        return $validated;
    }

    private function normalizeLegacySubspaceInput(Request $request): void
    {
        if (!$request->has('sub_espacios') && $request->filled('sub_espacio_id')) {
            $request->merge(['sub_espacios' => [(int) $request->input('sub_espacio_id')]]);
        }
    }

    private function requestedQuantities(array $validated): array
    {
        $quantities = [];

        foreach ($validated['recursos'] ?? [] as $selection) {
            $quantity = (int) $selection['cantidad'];
            if ($quantity > 0) {
                $quantities[(int) $selection['id']] = $quantity;
            }
        }

        ksort($quantities);

        return $quantities;
    }
}
