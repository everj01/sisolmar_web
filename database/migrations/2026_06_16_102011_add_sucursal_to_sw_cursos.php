<?php

  use Illuminate\Database\Migrations\Migration;
  use Illuminate\Database\Schema\Blueprint;
  use Illuminate\Support\Facades\Schema;

  return new class extends Migration
  {
      public function up(): void
      {
          Schema::table('sw_cursos', function (Blueprint $table) {
              $table->string('sucursal', 10)->nullable()->after('dirigido_a');
          });
      }

      public function down(): void
      {
          Schema::table('sw_cursos', function (Blueprint $table) {
              $table->dropColumn('sucursal');
          });
      }
  };