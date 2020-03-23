<?php

namespace SocketServer\JuegoCartas;

class Partida{

    public $idPartida;
    public $jugador1;
    public $jugador2;

    public function __construct( $idPartida ) {
        $this->idPartida = $idPartida;
    }

}