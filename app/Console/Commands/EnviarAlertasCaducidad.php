<?php

namespace App\Console\Commands;

use App\Mail\AlertaCaducidadMail;
use App\Models\NotificacionModel;
use Illuminate\Console\Command;
use Mail;

class EnviarAlertasCaducidad extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:enviar-alertas-caducidad';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Envía correos de documentos por vencer a cada personal';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dias = 10;

        $rows = NotificacionModel::foliosPorVencer($dias);

        if (empty($rows)) {
            $this->info('No hay documentos por vencer');
            return;
        }

        $personas = [];

        foreach ($rows as $row) {
            $cod = $row->codPersonal;

            if (!isset($personas[$cod])) {
                $personas[$cod] = [
                    'codPersonal' => $cod,
                    'nombre'     => $row->personal,
                    'email'      => $row->email ?? null,
                    'documentos' => []
                ];
            }

            $personas[$cod]['documentos'][] = [
                'nombre'          => $row->documento,
                'fecha_caducidad' => date('d/m/Y', strtotime($row->fecha_caducidad)),
                'dias_restantes'  => $row->dias_restantes,
            ];
        }

        $enviados = 0;

        foreach ($personas as $persona) {

            if (empty($persona['email'])) {
                continue;
            }

            Mail::to($persona['email'])
                ->send(new AlertaCaducidadMail([
                    'nombre_personal' => $persona['nombre'],
                    'nombre_empresa'  => 'SISOLMAR',
                    'documentos'      => $persona['documentos']
                ]));

            $enviados++;
        }

        $this->info("Correos enviados: {$enviados}");
    }
}
