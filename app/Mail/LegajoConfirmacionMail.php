<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class LegajoConfirmacionMail extends Mailable
{
    use Queueable, SerializesModels;

    public array $data;

    /**
     * Create a new message instance.
     *
     * @param array $data ['nombre_personal', 'nombre_empresa', 'nombre_legajo', 'nombre_cargo', 'nombre_folio']
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('✅ Nuevo Legajo Añadido - ' . $this->data['nombre_empresa'])
                    ->view('emails.legajo-confirmacion')
                    ->with($this->data);
    }
}
