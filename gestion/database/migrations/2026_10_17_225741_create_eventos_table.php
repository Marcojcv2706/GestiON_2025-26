<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('eventos', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('description')->nullable();
            
            // Campos de tiempo requeridos por el calendario
            $table->dateTime('start_time');
            $table->dateTime('end_time');

            // Llaves foráneas combinadas (opcionales dependiendo del tipo de evento)
            $table->foreignId('espacio_id')
                ->nullable()
                ->constrained('espacios')
                ->onDelete('cascade');

            $table->foreignId('sub_espacio_id')
                ->nullable()
                ->constrained('sub_espacios')
                ->onDelete('cascade');
                
            $table->foreignId('recurso_id')
                ->nullable()
                ->constrained('recursos')
                ->onDelete('cascade');

            $table->foreignId('user_id')
                ->comment('ID del Profesor/Usuario responsable')
                ->constrained('users')
                ->onDelete('cascade');

            $table->softDeletes(); // Para mantener el deleted_at que usabas en eventos
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eventos');
    }
};