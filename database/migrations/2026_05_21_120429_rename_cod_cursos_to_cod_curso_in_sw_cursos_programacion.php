<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sw_cursos_programacion', function (Blueprint $table) {
            $table->renameColumn('cod_cursos', 'cod_curso');
        });
    }

    public function down(): void
    {
        Schema::table('sw_cursos_programacion', function (Blueprint $table) {
            $table->renameColumn('cod_curso', 'cod_cursos');
        });
    }
};
