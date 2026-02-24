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
        Schema::create('sw_folio_encargado', function (Blueprint $table) {
            $table->smallInteger('cod_folio')->primary();
            $table->smallInteger('cod_rol');
            
            // Llaves foráneas a las tablas existentes
            $table->foreign('cod_folio')->references('codigo')->on('sw_folios');
            $table->foreign('cod_rol')->references('codigo')->on('sw_roles');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sw_folio_encargado');
    }
};
