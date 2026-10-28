<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SolicitudEvento extends Model
{
    public const PENDIENTE = 'pendiente';
    public const APROBADA = 'aprobada';
    public const RECHAZADA = 'rechazada';
    public const MODIFICACION_SOLICITADA = 'modificacion_solicitada';

    protected $table = 'solicitudes_eventos';

    protected $fillable = [
        'user_id',
        'revisado_por',
        'evento_id',
        'espacio_id',
        'sub_espacio_id',
        'name',
        'description',
        'start_time',
        'end_time',
        'estado',
        'observacion_admin',
        'decidido_en',
    ];

    protected function casts(): array
    {
        return [
            'start_time' => 'datetime',
            'end_time' => 'datetime',
            'decidido_en' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function revisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revisado_por');
    }

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
        return $this->belongsToMany(SubEspacio::class, 'solicitud_evento_sub_espacio')
            ->withTimestamps()
            ->orderBy('sub_espacios.name');
    }

    public function evento(): BelongsTo
    {
        return $this->belongsTo(Evento::class);
    }

    public function recursos(): BelongsToMany
    {
        return $this->belongsToMany(Recurso::class, 'solicitud_evento_recurso')
            ->withPivot('cantidad')
            ->withTimestamps();
    }

    public function decisiones(): HasMany
    {
        return $this->hasMany(SolicitudEventoDecision::class, 'solicitud_evento_id');
    }
}
