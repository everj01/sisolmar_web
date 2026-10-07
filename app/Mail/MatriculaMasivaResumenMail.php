<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MatriculaMasivaResumenMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $nombreCurso,
        public readonly string $programacion,
        public readonly int $total,
        public readonly int $enviados,
        public readonly int $fallidos,
        public readonly string $origen,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Matrícula masiva finalizada: {$this->nombreCurso}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.matricula-masiva-resumen',
        );
    }
}
