
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

            $table->foreignId('historial_id')
                ->constrained('historials')
                ->cascadeOnDelete();

            $table->foreignId('recurso_id')
                ->nullable()
                ->constrained('recursos')
                ->nullOnDelete();

            $table->foreignId('solicitante_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('recurso_nombre');
            $table->unsignedInteger('cantidad')->default(1);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('historial_recursos');
    }
};
