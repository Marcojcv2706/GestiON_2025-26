<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('eventos', 'estado')) {
            Schema::table('eventos', function (Blueprint $table) {
                $table->string('estado')->default('programado');
            });
        }
    }

    public function down(): void
    {
        // Keep the repaired status column; it is required by current event workflows.
    }
};
