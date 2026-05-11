<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RecordatorioCursoMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $usuario;

    public function __construct($usuario)
    {
        $this->usuario = $usuario;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Recordatorio: ' . $this->usuario->course_name,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.recordatorio-curso',
            with: [
                'full_name'             => $this->usuario->full_name,
                'course_name'           => $this->usuario->course_name,
                'course_shortname'      => $this->usuario->course_shortname,
                'enrolment_start_date'  => $this->usuario->enrolment_start_date,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}