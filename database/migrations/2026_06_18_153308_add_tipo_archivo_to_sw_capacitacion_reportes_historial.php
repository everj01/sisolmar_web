<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::connection('sqlsrv')->statement(
            "ALTER TABLE sw_capacitacion_reportes_historial ADD tipo_archivo VARCHAR(10) NULL"
        );

        DB::connection('sqlsrv')->update(
            "UPDATE sw_capacitacion_reportes_historial SET tipo_archivo = 'pdf' WHERE archivo_pdf IS NOT NULL AND tipo_archivo IS NULL"
        );
        DB::connection('sqlsrv')->update(
            "UPDATE sw_capacitacion_reportes_historial SET tipo_archivo = 'xlsx' WHERE archivo_excel IS NOT NULL AND tipo_archivo IS NULL"
        );
    }

    public function down(): void
    {
        DB::connection('sqlsrv')->statement(
            "ALTER TABLE sw_capacitacion_reportes_historial DROP COLUMN tipo_archivo"
        );
    }
};
