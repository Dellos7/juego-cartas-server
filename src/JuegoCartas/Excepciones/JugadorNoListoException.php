<?php

namespace SocketServer\JuegoCartas\Excepciones;

use Exception;

class JugadorNoListoException extends Exception {

    public function __construct( string $message = "Algún jugador no está listo." )
    {
        parent::__construct( $message );
    }
}