<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;

class Correos extends Mailable
{
    public $codigo;

    public function __construct($codigo)
    {
        $this->codigo = $codigo;
    }

    public function build()
    {
        return $this
            ->subject('Verificación de correo')
            ->view('correos.correo');
    }
}
