<?php

namespace App\Http\Controllers;

use App\Models\Recurso;
use App\Services\RecursoExcelImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class RecursoController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'buscar' => ['nullable', 'string', 'max:120'],
            'tipo' => ['nullable', Rule::in(Recurso::CATEGORIAS)],
            'estado' => ['nullable', Rule::in(['disponible', 'sin_unidades'])],
            'orden' => ['nullable', Rule::in(['name', 'tipo', 'cantidad_total', 'cantidad_disponible', 'estado'])],
            'direccion' => ['nullable', Rule::in(['asc', 'desc'])],
            'por_pagina' => ['nullable', Rule::in(['10', '25', '50', '100'])],
        ]);

        $query = Recurso::query();
        $buscar = trim($validated['buscar'] ?? '');

        if ($buscar !== '') {
            $query->where(function ($resources) use ($buscar) {
                $resources->where('name', 'like', "%{$buscar}%")
                    ->orWhere('tipo', 'like', "%{$buscar}%")
                    ->orWhere('descripcion', 'like', "%{$buscar}%");
            });
        }

        if (! empty($validated['tipo'])) {
            $query->where('tipo', $validated['tipo']);
        }

        if (($validated['estado'] ?? null) === 'disponible') {
            $query->where('cantidad_disponible', '>', 0);
        } elseif (($validated['estado'] ?? null) === 'sin_unidades') {
            $query->where('cantidad_disponible', '<=', 0);
        }

        $orden = $validated['orden'] ?? 'name';
        $direccion = $validated['direccion'] ?? 'asc';
        if ($orden === 'estado') {
            $query->orderByRaw("CASE WHEN cantidad_disponible > 0 THEN 'Disponible' ELSE 'Sin unidades' END {$direccion}");
        } else {
            $query->orderBy($orden, $direccion);
        }

        $recursos = $query
            ->orderBy('id')
            ->paginate((int) ($validated['por_pagina'] ?? 25))
            ->withQueryString();

        return view('admin.recursos.index', [
            'recursos' => $recursos,
            'tipos' => Recurso::CATEGORIAS,
            'filtros' => [
                'buscar' => $buscar,
                'tipo' => $validated['tipo'] ?? '',
                'estado' => $validated['estado'] ?? '',
                'orden' => $orden,
                'direccion' => $direccion,
                'por_pagina' => (int) ($validated['por_pagina'] ?? 25),
            ],
        ]);
    }

    public function create()
    {
        return view('admin.recursos.create', ['tipos' => Recurso::CATEGORIAS]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:recursos,name'],
            'tipo' => ['required', Rule::in(Recurso::CATEGORIAS)],
            'descripcion' => ['nullable', 'string', 'max:1000'],
            'cantidad_total' => ['required', 'integer', 'min:1'],
        ]);

        Recurso::create([
            ...$validated,
            'cantidad_disponible' => $validated['cantidad_total'],
        ]);

        return redirect()->route('admin.recursos.index')->with('success', 'Recurso creado con todas sus unidades disponibles.');
    }

    public function edit(Recurso $recurso)
    {
        return view('admin.recursos.edit', [
            'recurso' => $recurso,
            'tipos' => Recurso::CATEGORIAS,
        ]);
    }

    public function update(Request $request, Recurso $recurso)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('recursos', 'name')->ignore($recurso->id)],
            'tipo' => ['required', Rule::in(Recurso::CATEGORIAS)],
            'descripcion' => ['nullable', 'string', 'max:1000'],
            'cantidad_total' => ['required', 'integer', 'min:1'],
        ]);

        DB::transaction(function () use ($validated, $recurso) {
            $lockedRecurso = Recurso::query()->lockForUpdate()->findOrFail($recurso->id);
            $enUso = $lockedRecurso->cantidadEnUso;

            if ($validated['cantidad_total'] < $enUso) {
                throw ValidationException::withMessages([
                    'cantidad_total' => "El total no puede ser menor que las {$enUso} unidades reservadas.",
                ]);
            }

            $lockedRecurso->fill($validated);
            $lockedRecurso->cantidad_disponible = $validated['cantidad_total'] - $enUso;
            $lockedRecurso->save();
        });

        return redirect()->route('admin.recursos.index')->with('success', 'Recurso actualizado; las reservas existentes se conservaron.');
    }

    public function destroy(Recurso $recurso)
    {
        if ($recurso->eventosConReserva()->exists() || $recurso->historialRecursos()->exists()) {
            return redirect()->route('admin.recursos.index')
                ->with('error', 'No se puede eliminar un recurso que tiene reservas o registros históricos.');
        }

        $recurso->delete();

        return redirect()->route('admin.recursos.index')->with('success', 'Recurso eliminado.');
    }

    public function downloadTemplate(RecursoExcelImportService $importer): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        return $importer->downloadTemplate();
    }

    public function importForm(Request $request): \Illuminate\Contracts\View\View
    {
        $token = $request->query('previsualizacion');
        $preview = is_string($token)
            ? Cache::get($this->previewCacheKey($request, $token))
            : null;

        return view('admin.recursos.import', [
            'preview' => $preview,
            'previewToken' => $preview ? $token : null,
            'previewExpired' => is_string($token) && ! $preview,
        ]);
    }

    public function previewImport(Request $request, RecursoExcelImportService $importer): RedirectResponse
    {
        $validated = $request->validate([
            'archivo' => ['required', 'file', 'mimes:xlsx,xls', 'max:5120'],
        ], [
            'archivo.mimes' => 'El archivo debe ser un Excel .xlsx o .xls válido.',
            'archivo.max' => 'El archivo no puede superar los 5 MB.',
        ]);

        $preview = $importer->preview($validated['archivo']->getRealPath());
        $token = Str::random(64);
        Cache::put($this->previewCacheKey($request, $token), $preview, now()->addMinutes(30));

        return redirect()->route('admin.recursos.importar', ['previsualizacion' => $token]);
    }

    public function confirmImport(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'previsualizacion' => ['required', 'string', 'size:64', 'regex:/^[A-Za-z0-9]+$/'],
        ]);
        $cacheKey = $this->previewCacheKey($request, $validated['previsualizacion']);
        $preview = Cache::get($cacheKey);

        if (! $preview) {
            return redirect()->route('admin.recursos.importar')
                ->with('error', 'La previsualización venció. Sube el archivo nuevamente.');
        }

        if ($preview['error_count'] > 0 || $preview['valid_count'] === 0 || $preview['global_errors']) {
            return redirect()->route('admin.recursos.importar', [
                'previsualizacion' => $validated['previsualizacion'],
            ])->with('error', 'Corrige todos los errores antes de confirmar la importación.');
        }

        $rows = collect($preview['rows'])->pluck('data')->all();
        $now = now();

        DB::transaction(function () use ($rows, $now) {
            $names = collect($rows)->pluck('name')->all();
            $existingNames = Recurso::query()
                ->whereIn('name', $names)
                ->pluck('name')
                ->map(fn ($name) => mb_strtolower($name))
                ->all();

            if ($existingNames !== []) {
                throw ValidationException::withMessages([
                    'archivo' => 'Uno o más nombres ya fueron registrados desde que se generó la previsualización. Sube el archivo nuevamente para revisarlo.',
                ]);
            }

            foreach (array_chunk($rows, 100) as $chunk) {
                DB::table('recursos')->insert(array_map(fn ($row) => [
                    'name' => $row['name'],
                    'tipo' => $row['tipo'],
                    'descripcion' => $row['descripcion'] !== '' ? $row['descripcion'] : null,
                    'cantidad_total' => $row['cantidad_total'],
                    'cantidad_disponible' => $row['cantidad_total'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ], $chunk));
            }
        });

        Cache::forget($cacheKey);

        return redirect()->route('admin.recursos.index')
            ->with('success', count($rows).' implementos importados correctamente.');
    }

    private function previewCacheKey(Request $request, string $token): string
    {
        return 'resource-import-preview:'.$request->user()->id.':'.$token;
    }
}
