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
        Schema::create('SW_MEMO_RECORDATORIOS', function (Blueprint $table) {
            $table->ID();
            $table->unsignedBigInteger('NRO_DOCU_IDEN');
            $table->unsignedBigInteger('MOODLE_USER_ID');
            $table->string('NOMBRE_COMPLETO');
            $table->unsignedTinyInteger('NUM_MEMO');
            $table->timestamp('FECHA_ENVIO')->useCurrent();
            $table->index('NRO_DOCU_IDEN');
        });

        Schema::create('SW_MEMO_RECORDATORIOS_CURSOS', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('MEMO_RECORDATORIO_ID');
            $table->unsignedBigInteger('CODIGO_MOODLE')->nullable();
            $table->string('NOMBRE_CURSO');
            $table->foreign('MEMO_RECORDATORIO_ID')
                ->references('id')
                ->on('SW_MEMO_RECORDATORIOS')
                ->onDelete('cascade');
            $table->index('MEMO_RECORDATORIO_ID');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('memo_recordatorios_cursos');
        Schema::dropIfExists('memo_recordatorios');
    }
};
