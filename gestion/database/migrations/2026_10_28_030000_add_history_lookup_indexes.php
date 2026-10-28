<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('historials', function (Blueprint $table) {
            $table->index('fecha_realizacion', 'historials_fecha_realizacion_index');
            $table->index('solicitante_id', 'historials_solicitante_id_index');
            $table->index('responsable_id', 'historials_responsable_id_index');
        });
    }

    public function down(): void
    {
        Schema::table('historials', function (Blueprint $table) {
            $table->dropIndex('historials_fecha_realizacion_index');
            $table->dropIndex('historials_solicitante_id_index');
            $table->dropIndex('historials_responsable_id_index');
        });
    }
};
