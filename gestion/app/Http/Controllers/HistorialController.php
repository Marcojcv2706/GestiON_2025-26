<?php

namespace App\Http\Controllers;

use App\Models\Historial;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class HistorialController extends Controller
{
    public function index(Request $request)
    {
        abort_if(!Gate::allows('es-admin'), 403);

        $historiales = Historial::with([
            'responsable',
            'solicitante',
            'recursos',
        ])
            ->orderByDesc('fecha_realizacion')
            ->paginate(10);

        return view('historial.index', compact('historiales'));
    }
}
