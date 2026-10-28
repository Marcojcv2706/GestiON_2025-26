<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('historial_recursos', function (Blueprint $table) {
            $table->id();
            
            // Relaciones
            $table->foreignId('historial_id')->constrained('historials')->onDelete('cascade');
            $table->foreignId('recurso_id')->constrained('recursos')->onDelete('cascade');
            $table->foreignId('solicitante_id')->constrained('users')->onDelete('cascade');
            
            // Otros campos que vi en tu error
            $table->string('recurso_nombre');
            $table->integer('cantidad');
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('historial_recursos');
    }
};