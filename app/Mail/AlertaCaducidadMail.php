<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AlertaCaducidadMail extends Mailable
{
    use Queueable, SerializesModels;

    public array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function build()
    {
        return $this->subject('Reporte de documentos por vencer')
            ->view('emails.alerta-caducidad')
            ->attach(public_path('pdfs/folios_pendientes.pdf'), [
                'as' => 'reporte_documentos_pendientes.pdf',
                'mime' => 'application/pdf',
            ]);
    }


    // public function build()
    // {
    //     return $this->subject('⚠️ Documento próximo a vencer')
    //                 ->view('emails.alerta-caducidad')
    //                 ->with($this->data);
    // }
}
