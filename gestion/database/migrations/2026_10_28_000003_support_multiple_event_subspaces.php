<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('eventos', function (Blueprint $table) {
            $table->foreignId('sub_espacio_id')->nullable()->change();
        });

        Schema::table('solicitudes_eventos', function (Blueprint $table) {
            $table->foreignId('sub_espacio_id')->nullable()->change();
        });

        Schema::table('historials', function (Blueprint $table) {
            $table->text('espacio_nombre')->nullable()->change();
        });

        Schema::create('evento_sub_espacio', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evento_id')->constrained('eventos')->cascadeOnDelete();
            $table->foreignId('sub_espacio_id')->constrained('sub_espacios')->restrictOnDelete();
            $table->timestamps();
            $table->unique(['evento_id', 'sub_espacio_id'], 'evento_subespacio_unique');
        });

        Schema::create('solicitud_evento_sub_espacio', function (Blueprint $table) {
            $table->id();
            $table->foreignId('solicitud_evento_id')->constrained('solicitudes_eventos')->cascadeOnDelete();
            $table->foreignId('sub_espacio_id')->constrained('sub_espacios')->restrictOnDelete();
            $table->timestamps();
            $table->unique(['solicitud_evento_id', 'sub_espacio_id'], 'solicitud_subespacio_unique');
        });

        $now = now();
        DB::table('eventos')
            ->whereNotNull('sub_espacio_id')
            ->orderBy('id')
            ->get(['id', 'sub_espacio_id'])
            ->each(function ($evento) use ($now) {
                DB::table('evento_sub_espacio')->insert([
                    'evento_id' => $evento->id,
                    'sub_espacio_id' => $evento->sub_espacio_id,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            });

        DB::table('solicitudes_eventos')
            ->whereNotNull('sub_espacio_id')
            ->orderBy('id')
            ->get(['id', 'sub_espacio_id'])
            ->each(function ($solicitud) use ($now) {
                DB::table('solicitud_evento_sub_espacio')->insert([
                    'solicitud_evento_id' => $solicitud->id,
                    'sub_espacio_id' => $solicitud->sub_espacio_id,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('solicitud_evento_sub_espacio');
        Schema::dropIfExists('evento_sub_espacio');
    }
};
