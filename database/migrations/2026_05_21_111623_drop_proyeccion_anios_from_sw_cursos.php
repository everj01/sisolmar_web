<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sw_cursos', function (Blueprint $table) {
            $table->dropColumn('proyeccion_anios');
        });
    }

    public function down(): void
    {
        Schema::table('sw_cursos', function (Blueprint $table) {
            $table->integer('proyeccion_anios')->nullable()->after('frecuencia');
        });
    }
};
