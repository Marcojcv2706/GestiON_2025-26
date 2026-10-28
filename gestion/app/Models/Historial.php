<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Historial extends Model
{
    protected $table = 'historials';

    protected $fillable = [
        'evento_id',
        'responsable_id',
        'solicitante_id',
        'evento_nombre',
        'descripcion',
        'espacio_nombre',
        'fecha_realizacion',
    ];

    protected $casts = [
        'fecha_realizacion' => 'datetime',
    ];

    public function evento(): BelongsTo
    {
        return $this->belongsTo(Evento::class);
    }

    public function responsable(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsable_id');
    }

    public function solicitante(): BelongsTo
    {
        return $this->belongsTo(User::class, 'solicitante_id');
    }

    public function recursos(): HasMany
    {
        return $this->hasMany(HistorialRecurso::class);
    }
}
