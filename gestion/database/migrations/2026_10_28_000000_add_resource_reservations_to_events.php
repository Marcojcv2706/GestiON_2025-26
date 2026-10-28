<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recursos', function (Blueprint $table) {
            if (!Schema::hasColumn('recursos', 'tipo')) {
                $table->string('tipo')->default('General');
            }
        });

        Schema::table('eventos', function (Blueprint $table) {
            if (!Schema::hasColumn('eventos', 'estado')) {
                $table->string('estado')->default('programado');
            }
        });

        Schema::create('evento_recurso', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evento_id')->constrained('eventos')->cascadeOnDelete();
            $table->foreignId('recurso_id')->constrained('recursos')->cascadeOnDelete();
            $table->unsignedInteger('cantidad')->default(1);
            $table->timestamps();
            $table->unique(['evento_id', 'recurso_id']);
        });

        DB::table('eventos')
            ->whereNotNull('recurso_id')
            ->whereNull('deleted_at')
            ->orderBy('id')
            ->get(['id', 'recurso_id'])
            ->each(function ($evento) {
                DB::table('evento_recurso')->insertOrIgnore([
                    'evento_id' => $evento->id,
                    'recurso_id' => $evento->recurso_id,
                    'cantidad' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            });

        DB::table('recursos')->orderBy('id')->get()->each(function ($recurso) {
            $total = (int) $recurso->cantidad_total;
            $disponible = min((int) $recurso->cantidad_disponible, $total);
            $enUso = DB::table('evento_recurso')
                ->join('eventos', 'eventos.id', '=', 'evento_recurso.evento_id')
                ->where('evento_recurso.recurso_id', $recurso->id)
                ->whereNull('eventos.deleted_at')
                ->sum('evento_recurso.cantidad');
            $enUso = max($total - $disponible, (int) $enUso);
            $total = max($total, $enUso);

            DB::table('recursos')
                ->where('id', $recurso->id)
                ->update([
                    'cantidad_total' => $total,
                    'cantidad_disponible' => $total - $enUso,
                ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evento_recurso');

        Schema::table('eventos', function (Blueprint $table) {
            if (Schema::hasColumn('eventos', 'estado')) {
                $table->dropColumn('estado');
            }
        });

        Schema::table('recursos', function (Blueprint $table) {
            if (Schema::hasColumn('recursos', 'tipo')) {
                $table->dropColumn('tipo');
            }
        });
    }
};
