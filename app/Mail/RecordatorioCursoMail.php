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

class RecordatorioCursoMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly object $usuario,
        public readonly object $curso,
        public readonly int    $numeroMemo,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Aviso N°{$this->numeroMemo}: {$this->curso->course_name}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.recordatorio-curso',
            with: [
                'full_name'            => $this->usuario->full_name,
                'course_name'          => $this->curso->course_name,
                'course_shortname'     => $this->curso->course_shortname,
                'enrolment_start_date' => $this->curso->enrolment_start_date,
                'numero_memo'          => $this->numeroMemo,
            ],
        );
    }

    public function attachments(): array
    {
        $pdf = Pdf::loadView("emails.memorandum-{$this->numeroMemo}-cursos", [
            'nombreCompleto' => $this->usuario->full_name,
            'fecha'          => now()->format('d/m/Y'),
            'cursos'         => [$this->curso],
        ]);

        return [
            Attachment::fromData(
                fn() => $pdf->output(),
                "MEMORANDUM-{$this->numeroMemo}-RRHH.pdf"
            )->withMime('application/pdf'),
        ];
    }
}
