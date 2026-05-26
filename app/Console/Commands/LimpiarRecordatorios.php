<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LimpiarRecordatorios extends Command
{
    protected $signature = 'capacitacion:limpiar-memos-recordatorios
                            {--force : Ejecuta la limpieza sin confirmación}';

    protected $description = 'Elimina todos los registros de SW_MEMO_RECORDATORIOS y SW_MEMO_RECORDATORIOS_CURSOS';

    public function handle(): int
    {
        if (!$this->option('force') && !$this->confirm('¿Estás seguro de eliminar todos los registros de recordatorios?')) {
            $this->info('Cancelado.');
            return self::SUCCESS;
        }

        $cursos = DB::table('SW_MEMO_RECORDATORIOS_CURSOS')->count();
        $memos  = DB::table('SW_MEMO_RECORDATORIOS')->count();

        DB::table('SW_MEMO_RECORDATORIOS_CURSOS')->delete();
        DB::table('SW_MEMO_RECORDATORIOS')->delete();

        Log::info("Recordatorios limpiados: {$memos} memo(s) y {$cursos} curso(s) eliminados.");

        $this->info("Se eliminaron {$memos} registro(s) de SW_MEMO_RECORDATORIOS y {$cursos} de SW_MEMO_RECORDATORIOS_CURSOS.");

        return self::SUCCESS;
    }
}
