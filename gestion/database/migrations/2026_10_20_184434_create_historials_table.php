<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('historials', function (Blueprint $table) {
            $table->id();

            $table->foreignId('evento_id')
                ->nullable()
                ->constrained('eventos')
                ->nullOnDelete();

            $table->foreignId('responsable_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('solicitante_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('evento_nombre');
            $table->text('descripcion')->nullable();
            $table->string('espacio_nombre')->nullable();
            $table->dateTime('fecha_realizacion')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('historials');
    }
};
