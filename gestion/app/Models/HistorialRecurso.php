<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HistorialRecurso extends Model
{
    protected $table = 'historial_recursos';

    protected $fillable = [
        'historial_id',
        'recurso_id',
        'recurso_nombre',
        'cantidad',
        'solicitante_id',
    ];

    public function historial(): BelongsTo
    {
        return $this->belongsTo(Historial::class);
    }

    public function recurso(): BelongsTo
    {
        return $this->belongsTo(Recurso::class);
    }

    public function solicitante(): BelongsTo
    {
        return $this->belongsTo(User::class, 'solicitante_id');
    }
}
