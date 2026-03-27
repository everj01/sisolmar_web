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
        Schema::connection('sqlsrv')->create('sw_notificaciones_matriculas', function (Blueprint $table) {
            $table->id('codigo');
            $table->integer('usuario_id');
            $table->string('cod_curso', 50)->nullable();
            $table->string('nombre_curso', 255)->nullable();
            $table->integer('total_personas')->default(0);
            $table->integer('enviados')->default(0);
            $table->integer('fallidos')->default(0);
            $table->string('tipo', 50)->default('completado'); // completado, error_conexion, multiples_fallos
            $table->text('mensaje')->nullable();
            $table->boolean('leido')->default(false);
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('sqlsrv')->dropIfExists('sw_notificaciones_matriculas');
    }
};
