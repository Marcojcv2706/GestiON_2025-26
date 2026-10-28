<?php

namespace App\Http\Controllers;

use App\Models\Historial;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class HistorialController extends Controller
{
    public function index(Request $request)
    {
        $usuario = $request->user();
        $isAdmin = $usuario->can('es-admin');
        $validated = $request->validate([
            'buscar' => ['nullable', 'string', 'max:120'],
            'desde' => ['nullable', 'date'],
            'hasta' => ['nullable', 'date', 'after_or_equal:desde'],
            'usuario_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
        ]);

        $query = Historial::with([
            'responsable',
            'solicitante',
            'recursos',
        ]);

        if (!$isAdmin) {
            if ($usuario->can('gestionar-actividades')) {
                $query->where(function ($q) use ($usuario) {
                    $q->where('solicitante_id', $usuario->id)
                        ->orWhere('responsable_id', $usuario->id);
                });
            } else {
                $query->where('solicitante_id', $usuario->id);
            }
        }

        $buscar = trim($validated['buscar'] ?? '');
        if ($buscar !== '') {
            $query->where(function ($records) use ($buscar) {
                $records->where('evento_nombre', 'like', "%{$buscar}%")
                    ->orWhere('descripcion', 'like', "%{$buscar}%")
                    ->orWhere('espacio_nombre', 'like', "%{$buscar}%")
                    ->orWhereHas('responsable', fn ($users) => $users->where('name', 'like', "%{$buscar}%"))
                    ->orWhereHas('solicitante', fn ($users) => $users->where('name', 'like', "%{$buscar}%"))
                    ->orWhereHas('recursos', fn ($resources) => $resources->where('recurso_nombre', 'like', "%{$buscar}%"));
            });
        }

        if (!empty($validated['desde'])) {
            $query->where('fecha_realizacion', '>=', $validated['desde'].' 00:00:00');
        }

        if (!empty($validated['hasta'])) {
            $query->where('fecha_realizacion', '<', date('Y-m-d', strtotime($validated['hasta'].' +1 day')));
        }

        if ($isAdmin && !empty($validated['usuario_id'])) {
            $userId = (int) $validated['usuario_id'];
            $query->where(function ($records) use ($userId) {
                $records->where('solicitante_id', $userId)
                    ->orWhere('responsable_id', $userId);
            });
        }

        $historiales = $query
            ->orderByDesc('fecha_realizacion')
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        $usuarios = collect();
        if ($isAdmin) {
            $userIds = Historial::query()
                ->select('responsable_id')
                ->whereNotNull('responsable_id')
                ->union(
                    Historial::query()
                        ->select('solicitante_id')
                        ->whereNotNull('solicitante_id')
                );
            $usuarios = User::query()
                ->whereIn('id', $userIds)
                ->orderBy('name')
                ->get(['id', 'name']);
        }

        return view('historial.index', [
            'historiales' => $historiales,
            'usuarios' => $usuarios,
            'filtros' => [
                'buscar' => $buscar,
                'desde' => $validated['desde'] ?? '',
                'hasta' => $validated['hasta'] ?? '',
                'usuario_id' => $isAdmin ? (int) ($validated['usuario_id'] ?? 0) : 0,
            ],
            'isAdmin' => $isAdmin,
        ]);
    }
}