<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Recurso extends Model
{
    use HasFactory;

    public const CATEGORIAS = [
        'Deportivos',
        'Audiovisuales',
        'Otros',
    ];

    protected $table = 'recursos';

    protected $fillable = [
        'name',
        'descripcion',
        'tipo',
        'cantidad_total',
        'cantidad_disponible',
    ];

    protected function casts(): array
    {
        return [
            'cantidad_total' => 'integer',
            'cantidad_disponible' => 'integer',
        ];
    }

    public function eventos(): HasMany
    {
        return $this->hasMany(Evento::class);
    }

    public function eventosConReserva(): BelongsToMany
    {
        return $this->belongsToMany(Evento::class)
            ->withPivot('cantidad')
            ->withTimestamps();
    }

    public function solicitudesRecursos(): BelongsToMany
    {
        return $this->belongsToMany(SolicitudRecurso::class, 'recurso_solicitud')
            ->withPivot('cantidad')
            ->withTimestamps();
    }

    public function getCantidadEnUsoAttribute(): int
    {
        return max(0, (int) $this->cantidad_total - (int) $this->cantidad_disponible);
    }

    public function getEstadoInventarioAttribute(): string
    {
        return $this->cantidad_disponible > 0 ? 'Disponible' : 'Sin unidades';
    }

    public function historialRecursos(): HasMany
    {
        return $this->hasMany(HistorialRecurso::class);
    }
}
