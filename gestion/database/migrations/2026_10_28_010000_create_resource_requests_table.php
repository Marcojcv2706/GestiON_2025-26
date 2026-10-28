<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('solicitudes_recursos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('revisado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->date('fecha_uso');
            $table->text('motivo');
            $table->string('estado')->default('pendiente');
            $table->text('observacion_admin')->nullable();
            $table->timestamp('decidido_en')->nullable();
            $table->timestamp('devuelto_en')->nullable();
            $table->timestamps();
            $table->index(['estado', 'fecha_uso']);
            $table->index(['user_id', 'estado']);
        });

        Schema::create('recurso_solicitud', function (Blueprint $table) {
            $table->id();
            $table->foreignId('solicitud_recurso_id')
                ->constrained('solicitudes_recursos')
                ->cascadeOnDelete();
            $table->foreignId('recurso_id')->constrained('recursos')->restrictOnDelete();
            $table->unsignedInteger('cantidad');
            $table->timestamps();
            $table->unique(['solicitud_recurso_id', 'recurso_id'], 'recurso_solicitud_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recurso_solicitud');
        Schema::dropIfExists('solicitudes_recursos');
    }
};
