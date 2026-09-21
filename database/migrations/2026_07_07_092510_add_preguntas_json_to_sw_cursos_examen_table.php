<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sw_cursos_examen', function (Blueprint $table) {
            $table->text('preguntas_json')->nullable()->after('preguntas_balotario');
        });
    }

    public function down(): void
    {
        Schema::table('sw_cursos_examen', function (Blueprint $table) {
            $table->dropColumn('preguntas_json');
        });
    }
};
