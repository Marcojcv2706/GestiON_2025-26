<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Evento;

class DashboardController extends Controller
{
    /**
     * Muestra el dashboard principal de la aplicación.
     */
    public function index()
    {
        $proximosEventos = Evento::with(['user', 'espacio', 'subEspacio', 'subEspacios', 'recursos'])
                                ->where('start_time', '>=', now())
                                ->where('estado', 'programado')
                                ->orderBy('start_time', 'asc')
                                ->take(5)
                                ->get();

        return view('dashboard', compact('proximosEventos'));
    }

    public function testEventos()
    {
        $proximosEventos = Evento::with(['user', 'espacio', 'subEspacio'])
                                ->where('start_time', '>=', now())
                                ->orderBy('start_time', 'asc')
                                ->take(5)
                                ->get();
        dd($proximosEventos);
    }
}