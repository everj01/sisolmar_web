<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Message;
use Illuminate\Queue\SerializesModels;

class BienvenidaSipMail extends Mailable
{
    use Queueable, SerializesModels;

    public array $datos;

    public function __construct(array $datos)
    {
        $this->datos = $datos;
    }

    public function build(): static
    {
        return $this
            ->mailer('sip')
            ->from('robotsisolmar@solsecurity.pe', 'SISOL Robot')
            ->subject("¡{$this->datos['nombre_corto']}, Bienvenido al SIP de SOLMAR!")
            ->view('emails.bienvenida-sip');
    }
}
