
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recursos', function (Blueprint $table) {
            if (!Schema::hasColumn('recursos', 'descripcion')) {
                $table->text('descripcion')->nullable();
            }

            if (!Schema::hasColumn('recursos', 'cantidad_total')) {
                $table->unsignedInteger('cantidad_total')->default(0);
            }

            if (!Schema::hasColumn('recursos', 'cantidad_disponible')) {
                $table->unsignedInteger('cantidad_disponible')->default(0);
            }
        });
    }

    public function down(): void
    {
        Schema::table('recursos', function (Blueprint $table) {
            $columns = [];

            foreach ([
                'descripcion',
                'cantidad_total',
                'cantidad_disponible',
            ] as $column) {
                if (Schema::hasColumn('recursos', $column)) {
                    $columns[] = $column;
                }
            }

            if (!empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
};
