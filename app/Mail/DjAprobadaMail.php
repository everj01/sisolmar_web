<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class DjAprobadaMail extends Mailable
{
    use Queueable, SerializesModels;

    public $datosCorreo;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($datosCorreo)
    {
        $this->datosCorreo = $datosCorreo;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->from(env('MAIL_USERNAME'), 'Grupo Solmar - RRHH')
                    ->subject('Aprobación de información DJ - RRHH')
                    ->bcc([
                        'webmater@gruposolmar.com.pe', 
                        'proyectossw@gruposolmar.com.pe', 
                        'jhordantapiaespinoza@gmail.com'
                    ])
                    ->view('emails.dj_aprobada');
    }
}