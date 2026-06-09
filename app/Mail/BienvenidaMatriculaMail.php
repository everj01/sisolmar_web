<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BienvenidaMatriculaMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $nombrePersonal,
        public readonly string $nombreCurso,
        public readonly string $fechaInicio,
        public readonly string $fechaFin
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Bienvenido al curso: {$this->nombreCurso}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.bienvenida-curso',
        );
    }
}

;