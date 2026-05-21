<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('sw_cursos')
            ->where('periodicidad', '>', 0)
            ->update([
                'periodicidad' => DB::raw("CASE periodicidad
                    WHEN 12 THEN 1
                    WHEN 6  THEN 2
                    WHEN 4  THEN 3
                    WHEN 3  THEN 4
                    WHEN 2  THEN 6
                    WHEN 1  THEN 12
                    ELSE periodicidad
                END"),
            ]);
    }

    public function down(): void
    {
        DB::table('sw_cursos')
            ->where('periodicidad', '>', 0)
            ->update([
                'periodicidad' => DB::raw("CASE periodicidad
                    WHEN 1  THEN 12
                    WHEN 2  THEN 6
                    WHEN 3  THEN 4
                    WHEN 4  THEN 3
                    WHEN 6  THEN 2
                    WHEN 12 THEN 1
                    ELSE periodicidad
                END"),
            ]);
    }
};
