<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class SolicitudRecurso extends Model
{
    public const PENDIENTE = 'pendiente';
    public const APROBADA = 'aprobada';
    public const RECHAZADA = 'rechazada';
    public const DEVUELTA = 'devuelta';

    protected $table = 'solicitudes_recursos';

    protected $fillable = [
        'user_id',
        'revisado_por',
        'fecha_uso',
        'motivo',
        'estado',
        'observacion_admin',
        'decidido_en',
        'devuelto_en',
    ];

    protected function casts(): array
    {
        return [
            'fecha_uso' => 'date',
            'decidido_en' => 'datetime',
            'devuelto_en' => 'datetime',
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

    public function recursos(): BelongsToMany
    {
        return $this->belongsToMany(Recurso::class, 'recurso_solicitud')
            ->withPivot('cantidad')
            ->withTimestamps();
    }
}
