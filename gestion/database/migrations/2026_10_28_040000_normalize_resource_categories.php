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
            $table->string('tipo')->default('Otros')->change();
        });

        DB::table('recursos')
            ->where('tipo', 'Deportivo')
            ->update(['tipo' => 'Deportivos']);

        DB::table('recursos')
            ->where('tipo', 'Audiovisual')
            ->update(['tipo' => 'Audiovisuales']);

        DB::table('recursos')
            ->where(function ($query) {
                $query->whereNull('tipo')
                    ->orWhereNotIn('tipo', ['Deportivos', 'Audiovisuales', 'Otros']);
            })
            ->update(['tipo' => 'Otros']);
    }

    public function down(): void
    {
        // Category values are intentionally not reverted because legacy categories were merged into Otros.
    }
};
