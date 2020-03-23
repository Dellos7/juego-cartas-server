<?php

namespace SocketServer\JuegoCartas\Excepciones;

use Exception;

class PartidaNoExisteException extends Exception{

    public function __construct( string $message = "La partida a la que te intentas unir no existe." )
    {
        parent::__construct( $message );
    }

}