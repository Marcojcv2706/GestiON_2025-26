<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Evento extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name', 
        'description', 
        'start_time', 
        'end_time', 
        'espacio_id', 
        'sub_espacio_id', 
        'recurso_id', 
        'user_id',
        'estado',
    ];

    // ESTO CONVIERTE LOS STRINGS EN OBJETOS DE FECHA
    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
    ];

    public function espacio(): BelongsTo 
    { 
        return $this->belongsTo(Espacio::class); 
    }
    
    public function subEspacio(): BelongsTo 
    { 
        return $this->belongsTo(SubEspacio::class); 
    }

    public function subEspacios(): BelongsToMany
    {
        return $this->belongsToMany(SubEspacio::class, 'evento_sub_espacio')
            ->withTimestamps()
            ->orderBy('sub_espacios.name');
    }
    
    public function recurso(): BelongsTo
    { 
        return $this->belongsTo(Recurso::class); 
    }

    public function recursos(): BelongsToMany
    {
        return $this->belongsToMany(Recurso::class)
            ->withPivot('cantidad')
            ->withTimestamps();
    }
    
    public function user(): BelongsTo 
    { 
        return $this->belongsTo(User::class); 
    }

    public function solicitud(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(SolicitudEvento::class);
    }
}