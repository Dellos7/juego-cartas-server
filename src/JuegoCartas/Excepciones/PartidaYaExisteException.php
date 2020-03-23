<?php

namespace SocketServer\JuegoCartas\Excepciones;

use Exception;

class PartidaYaExisteException extends Exception {

    public function __construct( string $message = "La partida que estás intentando crear ya existe." )
    {
        parent::__construct( $message );
    }
}