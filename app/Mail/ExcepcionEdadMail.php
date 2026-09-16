<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ExcepcionEdadMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $datosCorreo;

    public function __construct(array $datosCorreo)
    {
        $this->datosCorreo = $datosCorreo;
    }

    public function build()
    {
        $asunto = 'Excepción de Edad - ' . $this->datosCorreo['nombre'];

        return $this->from(config('mail.from.address'), config('mail.from.name'))
                    ->subject($asunto)
                    ->to('jhordantapiaespinoza@gmail.com')
                    ->view('emails.excepcion_edad');
    }
}
