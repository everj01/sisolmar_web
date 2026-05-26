<?php

namespace App\Mail;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RecordatorioCursosPendientesMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly object  $usuario,
        public readonly array   $cursos,
        public readonly int     $numeroMemo,
        public readonly ?string $fechaPrimerMEMO = null,
        public readonly ?string $fechaSegundoMEMO = null,
    ) {
        $this->onQueue('emails');
    }

    public function envelope(): Envelope
    {
        $total = count($this->cursos);

        return new Envelope(
            subject: "Aviso N°{$this->numeroMemo} - Tienes {$total} cursos pendientes por iniciar",
        );
    }

    public function content(): Content
    {
        $cursosPendientes = array_map(fn($curso) => [
            'nombre' => $curso->course_name,
        ], $this->cursos);

        return new Content(
            view: 'emails.recordatorio-pendientes',
            with: [
                'full_name'          => $this->usuario->full_name,
                'cursos_pendientes'  => $cursosPendientes,
                'numero_memo'        => $this->numeroMemo,
            ],
        );
    }

    public function attachments(): array
    {
        $viewData = [
            'nombreCompleto'  => $this->usuario->full_name,
            'fecha'           => now()->format('d/m/Y'),
            'fechaActual'     => now()->format('d/m/Y'),
            'cursos'          => $this->cursos,
            'fechaPrimerMEMO' => $this->fechaPrimerMEMO,
        ];

        if ($this->fechaSegundoMEMO) {
            $viewData['fechaSegundoMEMO'] = $this->fechaSegundoMEMO;
        }

        $pdf = Pdf::loadView("emails.memorandum-{$this->numeroMemo}-cursos", $viewData);

        return [
            Attachment::fromData(
                fn() => $pdf->output(),
                "MEMORANDUM-{$this->numeroMemo}-RRHH.pdf"
            )->withMime('application/pdf'),
        ];
    }
}
