<?php

namespace App\Http\Controllers;

use App\Models\Espacio;
use App\Models\Evento;
use App\Models\Recurso;
use App\Models\SolicitudEvento;
use App\Models\SolicitudEventoDecision;
use App\Models\SubEspacio;
use App\Services\ResourceReservationService;
use App\Services\EventConflictService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SolicitudEventoController extends Controller
{
    public function index(Request $request)
    {
        $isAdmin = $request->user()->can('es-admin');
        abort_unless($isAdmin || $request->user()->can('es-profesor'), 403);
        $query = SolicitudEvento::with(['user', 'espacio', 'subEspacios', 'subEspacio', 'recursos'])
            ->latest();

        if (!$isAdmin) {
            $query->where('user_id', $request->user()->id);
        } elseif ($request->filled('estado')) {
            $query->where('estado', $request->string('estado'));
        }

        return view('solicitudes.index', [
            'solicitudes' => $query->paginate(12)->withQueryString(),
            'isAdmin' => $isAdmin,
            'estado' => $request->input('estado', ''),
        ]);
    }

    public function create()
    {
        return view('solicitudes.create', $this->formData());
    }

    public function store(Request $request)
    {
        $this->normalizeLegacySubspaceInput($request);
        $validated = $this->validateSolicitud($request);
        $solicitud = DB::transaction(function () use ($request, $validated) {
            $solicitud = SolicitudEvento::create([
                ...$this->solicitudFields($validated),
                'user_id' => $request->user()->id,
                'estado' => SolicitudEvento::PENDIENTE,
            ]);
            $solicitud->subEspacios()->sync($validated['sub_espacios'] ?? []);
            $solicitud->recursos()->sync($this->resourcePivot($validated));
            $this->logDecision($solicitud, $request, 'solicitud_enviada', null, SolicitudEvento::PENDIENTE);

            return $solicitud;
        });

        return redirect()->route('solicitudes.show', $solicitud)
            ->with('success', 'Tu solicitud fue enviada y está pendiente de revisión.');
    }

    public function show(Request $request, SolicitudEvento $solicitud)
    {
        $this->authorizeSolicitud($request, $solicitud);
        $solicitud->load([
            'user',
            'revisor',
            'espacio',
            'subEspacio',
            'subEspacios',
            'recursos',
            'evento',
            'decisiones.actor',
        ]);

        return view('solicitudes.show', [
            'solicitud' => $solicitud,
            'isAdmin' => $request->user()->can('es-admin'),
            ...$this->formData($solicitud),
        ]);
    }

    public function edit(Request $request, SolicitudEvento $solicitud)
    {
        $this->authorizeOwner($request, $solicitud);
        abort_unless($solicitud->estado === SolicitudEvento::MODIFICACION_SOLICITADA, 403);
        $solicitud->load(['recursos', 'subEspacios']);

        return view('solicitudes.edit', [
            'solicitud' => $solicitud,
            ...$this->formData($solicitud),
        ]);
    }

    public function update(Request $request, SolicitudEvento $solicitud)
    {
        $this->authorizeOwner($request, $solicitud);
        abort_unless($solicitud->estado === SolicitudEvento::MODIFICACION_SOLICITADA, 403);
        $this->normalizeLegacySubspaceInput($request);
        $validated = $this->validateSolicitud($request);

        DB::transaction(function () use ($request, $solicitud, $validated) {
            $locked = SolicitudEvento::query()->lockForUpdate()->findOrFail($solicitud->id);
            abort_unless($locked->user_id === $request->user()->id, 403);
            abort_unless($locked->estado === SolicitudEvento::MODIFICACION_SOLICITADA, 403);
            $locked->fill($this->solicitudFields($validated))->save();
            $locked->subEspacios()->sync($validated['sub_espacios'] ?? []);
            $locked->recursos()->sync($this->resourcePivot($validated));
            $this->logDecision(
                $locked,
                $request,
                'solicitud_corregida',
                SolicitudEvento::MODIFICACION_SOLICITADA,
                SolicitudEvento::MODIFICACION_SOLICITADA
            );
        });

        return redirect()->route('solicitudes.show', $solicitud)
            ->with('success', 'Cambios guardados. Revisa la solicitud y envíala nuevamente.');
    }

    public function reenviar(Request $request, SolicitudEvento $solicitud)
    {
        $this->authorizeOwner($request, $solicitud);

        DB::transaction(function () use ($request, $solicitud) {
            $locked = SolicitudEvento::query()->lockForUpdate()->findOrFail($solicitud->id);
            abort_unless($locked->user_id === $request->user()->id, 403);
            abort_unless($locked->estado === SolicitudEvento::MODIFICACION_SOLICITADA, 403);
            $locked->update([
                'estado' => SolicitudEvento::PENDIENTE,
                'observacion_admin' => null,
                'revisado_por' => null,
                'decidido_en' => null,
            ]);
            $this->logDecision(
                $locked,
                $request,
                'solicitud_reenviada',
                SolicitudEvento::MODIFICACION_SOLICITADA,
                SolicitudEvento::PENDIENTE
            );
        });

        return redirect()->route('solicitudes.show', $solicitud)
            ->with('success', 'Tu solicitud fue enviada nuevamente para revisión.');
    }

    public function aprobar(
        Request $request,
        SolicitudEvento $solicitud,
        ResourceReservationService $inventory,
        EventConflictService $conflicts
    )
    {
        abort_unless($request->user()->can('es-admin'), 403);

        DB::transaction(function () use ($request, $solicitud, $inventory, $conflicts) {
            $locked = SolicitudEvento::query()
                ->with(['recursos', 'user', 'subEspacios'])
                ->lockForUpdate()
                ->findOrFail($solicitud->id);
            $this->ensurePending($locked);

            $subspaceIds = $this->subspaceIds($locked);
            $conflicts->ensureSubspacesAvailable(
                $subspaceIds,
                (int) $locked->espacio_id,
                $locked->start_time,
                $locked->end_time
            );

            $evento = Evento::create([
                'name' => $locked->name,
                'description' => $locked->description,
                'start_time' => $locked->start_time,
                'end_time' => $locked->end_time,
                'espacio_id' => $locked->espacio_id,
                'sub_espacio_id' => $subspaceIds[0] ?? null,
                'user_id' => $locked->user_id,
                'estado' => 'programado',
            ]);
            $evento->subEspacios()->sync($subspaceIds);

            $inventory->sync($evento, $locked->recursos
                ->mapWithKeys(fn (Recurso $recurso) => [$recurso->id => (int) $recurso->pivot->cantidad])
                ->all(), true);

            $locked->update([
                'estado' => SolicitudEvento::APROBADA,
                'revisado_por' => $request->user()->id,
                'evento_id' => $evento->id,
                'observacion_admin' => null,
                'decidido_en' => now(),
            ]);
            $this->logDecision($locked, $request, 'aprobada', SolicitudEvento::PENDIENTE, SolicitudEvento::APROBADA);
        });

        return redirect()->route('solicitudes.show', $solicitud)
            ->with('success', 'Solicitud aprobada. El evento ya aparece en el calendario.');
    }

    public function rechazar(Request $request, SolicitudEvento $solicitud)
    {
        return $this->decidirConMotivo(
            $request,
            $solicitud,
            SolicitudEvento::RECHAZADA,
            'rechazada',
            'La solicitud fue rechazada.'
        );
    }

    public function solicitarModificacion(Request $request, SolicitudEvento $solicitud)
    {
        return $this->decidirConMotivo(
            $request,
            $solicitud,
            SolicitudEvento::MODIFICACION_SOLICITADA,
            'modificacion_solicitada',
            'Se solicitaron modificaciones a la solicitud.'
        );
    }

    public function modificarComoAdmin(Request $request, SolicitudEvento $solicitud)
    {
        abort_unless($request->user()->can('es-admin'), 403);
        $this->normalizeLegacySubspaceInput($request);
        $validated = $this->validateSolicitud($request);
        $validated['motivo'] = $request->validate([
            'motivo' => ['required', 'string', 'min:5', 'max:2000'],
        ])['motivo'];

        DB::transaction(function () use ($request, $solicitud, $validated) {
            $locked = SolicitudEvento::query()->with(['recursos', 'subEspacios'])->lockForUpdate()->findOrFail($solicitud->id);
            $this->ensurePending($locked);
            $changes = $this->changesFor($locked, $validated);
            $locked->fill($this->solicitudFields($validated))->save();
            $locked->subEspacios()->sync($validated['sub_espacios'] ?? []);
            $locked->recursos()->sync($this->resourcePivot($validated));
            $locked->update([
                'revisado_por' => $request->user()->id,
                'observacion_admin' => $validated['motivo'],
            ]);
            $this->logDecision(
                $locked,
                $request,
                'solicitud_modificada_por_admin',
                SolicitudEvento::PENDIENTE,
                SolicitudEvento::PENDIENTE,
                $validated['motivo'],
                $changes
            );
        });

        return redirect()->route('solicitudes.show', $solicitud)
            ->with('success', 'La solicitud fue modificada y el cambio quedó registrado.');
    }

    private function decidirConMotivo(
        Request $request,
        SolicitudEvento $solicitud,
        string $newState,
        string $action,
        string $message
    ) {
        abort_unless($request->user()->can('es-admin'), 403);
        $validated = $request->validate([
            'motivo' => ['required', 'string', 'min:5', 'max:2000'],
        ]);

        DB::transaction(function () use ($request, $solicitud, $newState, $action, $validated) {
            $locked = SolicitudEvento::query()->lockForUpdate()->findOrFail($solicitud->id);
            $this->ensurePending($locked);
            $previousState = $locked->estado;
            $locked->update([
                'estado' => $newState,
                'revisado_por' => $request->user()->id,
                'observacion_admin' => $validated['motivo'],
                'decidido_en' => now(),
            ]);
            $this->logDecision($locked, $request, $action, $previousState, $newState, $validated['motivo']);
        });

        return redirect()->route('solicitudes.show', $solicitud)->with('success', $message);
    }

    private function validateSolicitud(Request $request): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'start_time' => ['required', 'date', 'after:now'],
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

    private function solicitudFields(array $validated): array
    {
        return [
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
            'espacio_id' => $validated['espacio_id'],
            'sub_espacio_id' => $validated['sub_espacios'][0] ?? null,
        ];
    }

    private function normalizeLegacySubspaceInput(Request $request): void
    {
        if (!$request->has('sub_espacios') && $request->filled('sub_espacio_id')) {
            $request->merge(['sub_espacios' => [(int) $request->input('sub_espacio_id')]]);
        }
    }

    private function subspaceIds(SolicitudEvento $solicitud): array
    {
        $ids = $solicitud->subEspacios->pluck('id')->map(fn ($id) => (int) $id)->all();

        if ($ids === [] && $solicitud->sub_espacio_id) {
            $ids[] = (int) $solicitud->sub_espacio_id;
        }

        return $ids;
    }

    private function resourcePivot(array $validated): array
    {
        $pivot = [];
        foreach ($validated['recursos'] ?? [] as $selection) {
            $quantity = (int) $selection['cantidad'];
            if ($quantity > 0) {
                $pivot[(int) $selection['id']] = ['cantidad' => $quantity];
            }
        }

        return $pivot;
    }

    private function formData(?SolicitudEvento $solicitud = null): array
    {
        return [
            'solicitud' => $solicitud,
            'espacios' => Espacio::orderBy('name')->get(),
            'subEspacios' => SubEspacio::with('espacio')->orderBy('name')->get(),
            'recursos' => Recurso::orderBy('name')->get(),
        ];
    }

    private function authorizeSolicitud(Request $request, SolicitudEvento $solicitud): void
    {
        $isAdmin = $request->user()->can('es-admin');
        $isTeacher = $request->user()->can('es-profesor');
        abort_unless($isAdmin || ($isTeacher && $solicitud->user_id === $request->user()->id), 403);
    }

    private function authorizeOwner(Request $request, SolicitudEvento $solicitud): void
    {
        abort_unless(
            $request->user()->can('es-profesor') && $solicitud->user_id === $request->user()->id,
            403
        );
    }

    private function ensurePending(SolicitudEvento $solicitud): void
    {
        if ($solicitud->estado !== SolicitudEvento::PENDIENTE) {
            throw ValidationException::withMessages([
                'solicitud' => 'Solo se pueden revisar solicitudes pendientes.',
            ]);
        }
    }

    private function logDecision(
        SolicitudEvento $solicitud,
        Request $request,
        string $action,
        ?string $previousState,
        string $newState,
        ?string $reason = null,
        ?array $changes = null
    ): void {
        SolicitudEventoDecision::create([
            'solicitud_evento_id' => $solicitud->id,
            'actor_id' => $request->user()->id,
            'accion' => $action,
            'estado_anterior' => $previousState,
            'estado_nuevo' => $newState,
            'motivo' => $reason,
            'cambios' => $changes,
        ]);
    }

    private function changesFor(SolicitudEvento $solicitud, array $validated): array
    {
        $changes = [];
        foreach (['name', 'description', 'start_time', 'end_time', 'espacio_id'] as $field) {
            $oldValue = (string) $solicitud->{$field};
            $newValue = (string) ($validated[$field] ?? '');
            if ($oldValue !== $newValue) {
                $changes[$field] = ['anterior' => $oldValue, 'nuevo' => $newValue];
            }
        }

        $oldSubspaces = $this->subspaceIds($solicitud);
        $newSubspaces = array_map('intval', $validated['sub_espacios'] ?? []);
        sort($oldSubspaces);
        sort($newSubspaces);
        if ($oldSubspaces !== $newSubspaces) {
            $changes['sub_espacios'] = [
                'anterior' => SubEspacio::whereIn('id', $oldSubspaces)->orderBy('id')->pluck('name')->all(),
                'nuevo' => SubEspacio::whereIn('id', $newSubspaces)->orderBy('id')->pluck('name')->all(),
            ];
        }

        $oldResources = $solicitud->recursos
            ->mapWithKeys(fn (Recurso $recurso) => [$recurso->id => (int) $recurso->pivot->cantidad])
            ->all();
        $newResources = collect($this->resourcePivot($validated))
            ->map(fn (array $pivot) => (int) $pivot['cantidad'])
            ->all();
        if ($oldResources !== $newResources) {
            $changes['recursos'] = ['anterior' => $oldResources, 'nuevo' => $newResources];
        }

        return $changes;
    }
}
