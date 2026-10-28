<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

// --- ¡LÍNEA AÑADIDA AQUÍ! ---
use App\Models\Espacio; 

class SubEspacio extends Model
{
    use HasFactory;

    /**
     * Nombre de la tabla asociada.
     */
    protected $table = 'sub_espacios';

    /**
     * Atributos que se pueden asignar masivamente.
     */
    protected $fillable = [
        'name',
        'espacio_id',
        // Agrega otros campos si los tienes
    ];

    /**
     * Define la relación inversa con Espacio.
     * Un SubEspacio pertenece a un Espacio.
     */
    public function espacio()
    {
        // Ahora PHP sabe qué es 'Espacio::class'
        return $this->belongsTo(Espacio::class); 
    }

    /**
     * Define la relación con Evento.
     * Un SubEspacio puede tener muchos Eventos.
     */
    public function eventos(): BelongsToMany
    {
        return $this->belongsToMany(Evento::class, 'evento_sub_espacio')
            ->withTimestamps()
            ->orderBy('eventos.start_time');
    }

    public function solicitudesEvento(): BelongsToMany
    {
        return $this->belongsToMany(SolicitudEvento::class, 'solicitud_evento_sub_espacio')
            ->withTimestamps()
            ->orderBy('solicitudes_eventos.start_time');
    }

    /**
     * Define la relación con Actividad.
     * Un SubEspacio puede tener muchas Actividades.
     */
     public function actividades()
    {
        return $this->hasMany(Actividad::class);
    }
}