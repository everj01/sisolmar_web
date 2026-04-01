<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('sw_cursos_examen', function (Blueprint $table) {
            $table->integer('cantidad_preguntas')->nullable()->after('intentos');
            $table->integer('preguntas_balotario')->nullable()->after('cantidad_preguntas');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sw_cursos_examen', function (Blueprint $table) {
            $table->dropColumn(['cantidad_preguntas', 'preguntas_balotario']);
        });
    }
};
