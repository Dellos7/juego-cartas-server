<?php

namespace SocketServer\JuegoCartas;

class Jugador{
    
    public $nombre;
    public $id;

    public function __construct( $id, $nombre )
    {
        $this->id = $id;
        $this->nombre = $nombre;
    }

}