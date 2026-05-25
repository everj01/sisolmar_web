<?php

namespace App\Console\Commands;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class EnviarMemorandumPrueba extends Command
{
    protected $signature = 'app:enviar-memos-prueba';

    public function handle(): int
    {
        $email = 'rodrigolauramoreno@gmail.com';
        $nombreCompleto = 'Rodrigo Sebastián Laura Moreno';

        $cursos = collect([
            (object) ['course_name' => 'INDUCCIÓN A LA IA | 2026-05'],
            (object) ['course_name' => 'PRÁCTICAS LABORALES | 2026-05'],
            (object) ['course_name' => 'ABC SOL SECURITY | 2026-05'],
        ]);

        $fechaActual = now()->format('d/m/Y');
        $fechaPrimerMEMO = now()->subDays(2)->format('d/m/Y');
        $fechaSegundoMEMO = now()->format('d/m/Y');

        $pdfs = [];

        $pdfs[1] = Pdf::loadView("emails.memorandum-1-cursos", [
            'nombreCompleto' => $nombreCompleto,
            'fecha' => $fechaActual,
            'cursos'         => $cursos,
        ]);

        $pdfs[2] = Pdf::loadView("emails.memorandum-2-cursos", [
            'nombreCompleto'   => $nombreCompleto,
            'fechaActual' => $fechaActual,
            'fechaPrimerMEMO'   => $fechaPrimerMEMO,
            'cursos'           => $cursos,
        ]);

        $pdfs[3] = Pdf::loadView("emails.memorandum-3-cursos", [
            'nombreCompleto'    => $nombreCompleto,
            'fechaActual' => $fechaActual,
            'fechaPrimerMEMO'   => $fechaPrimerMEMO,
            'fechaSegundoMEMO'  => $fechaSegundoMEMO,
            'cursos'           => $cursos,
        ]);

        Mail::send([], [], function ($message) use ($email, $nombreCompleto, $pdfs) {
            $message->to($email, $nombreCompleto)
                ->subject('Notificación – Memorándums de Cursos Pendientes')
                ->html("<p>Estimado/a <strong>{$nombreCompleto}</strong>, se adjuntan los memorándums correspondientes.</p>");

            foreach ($pdfs as $num => $pdf) {
                $message->attachData(
                    $pdf->output(),
                    "MEMORANDUM-0{$num}-RRHH.pdf",
                    ['mime' => 'application/pdf']
                );
            }
        });

        $this->info("> Correo enviado a {$email} con los 3 PDFs");
        return self::SUCCESS;
    }
}
