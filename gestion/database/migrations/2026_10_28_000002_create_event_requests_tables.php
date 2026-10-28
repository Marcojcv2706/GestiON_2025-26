<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('solicitudes_eventos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('revisado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('evento_id')->nullable()->unique()->constrained('eventos')->nullOnDelete();
            $table->foreignId('espacio_id')->constrained('espacios')->restrictOnDelete();
            $table->foreignId('sub_espacio_id')->constrained('sub_espacios')->restrictOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->dateTime('start_time');
            $table->dateTime('end_time');
            $table->string('estado')->default('pendiente');
            $table->text('observacion_admin')->nullable();
            $table->timestamp('decidido_en')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'estado']);
            $table->index(['estado', 'start_time']);
        });

        Schema::create('solicitud_evento_recurso', function (Blueprint $table) {
            $table->id();
            $table->foreignId('solicitud_evento_id')->constrained('solicitudes_eventos')->cascadeOnDelete();
            $table->foreignId('recurso_id')->constrained('recursos')->restrictOnDelete();
            $table->unsignedInteger('cantidad');
            $table->timestamps();
            $table->unique(['solicitud_evento_id', 'recurso_id'], 'solicitud_recurso_unique');
        });

        Schema::create('solicitud_evento_decisiones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('solicitud_evento_id')->constrained('solicitudes_eventos')->cascadeOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('accion');
            $table->string('estado_anterior')->nullable();
            $table->string('estado_nuevo');
            $table->text('motivo')->nullable();
            $table->json('cambios')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('solicitud_evento_decisiones');
        Schema::dropIfExists('solicitud_evento_recurso');
        Schema::dropIfExists('solicitudes_eventos');
    }
};
