<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RecordatorioCursosPendientesMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly object $usuario,
        public readonly array  $cursos,
    ) {}

    public function envelope(): Envelope
    {
        $total = count($this->cursos);

        return new Envelope(
            subject: "Tienes {$total} cursos pendientes por iniciar",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.recordatorio-pendientes',
            with: [
                'full_name' => $this->usuario->full_name,
                'cursos'    => $this->cursos,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}