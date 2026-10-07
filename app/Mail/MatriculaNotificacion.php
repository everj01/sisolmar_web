<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MatriculaNotificacion extends Mailable
{
    use Queueable, SerializesModels;

    public $personal;
    public $curso;

    public function __construct($personal, $curso)
    {
        $this->personal = $personal;
        $this->curso = $curso;
    }

    public function build()
    {
        return $this->subject('Confirmación de Matrícula - ' . $this->curso->nombre)
                    ->from(config('mail.from.address'), config('mail.from.name'))
                    // ->bcc([
                    //     'administracion@icma.edu.pe',
                    //     'cursospesqueros@icma.edu.pe'
                    // ])
                    ->view('emails.matricula-notificacion', [$this->personal, $this->curso]);
    }
}
