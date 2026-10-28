<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SolicitudEventoDecision extends Model
{
    protected $table = 'solicitud_evento_decisiones';

    protected $fillable = [
        'solicitud_evento_id',
        'actor_id',
        'accion',
        'estado_anterior',
        'estado_nuevo',
        'motivo',
        'cambios',
    ];

    protected function casts(): array
    {
        return ['cambios' => 'array'];
    }

    public function solicitud(): BelongsTo
    {
        return $this->belongsTo(SolicitudEvento::class, 'solicitud_evento_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
