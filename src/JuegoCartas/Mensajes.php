<?php

namespace SocketServer\JuegoCartas;

class Mensajes{

    public static $MENSAJE_JUGADOR_UNIDO = '%s se ha unido a la partida.';
    public static $MENSAJE_COMIENZA_JUEGO = '¡Comienza la partida!';
    
    public static function getMensajeJugadorUnido( string $nombreJugador ): string{
        return sprintf( self::$MENSAJE_JUGADOR_UNIDO, $nombreJugador );
    }

    public static function getMensajeComienzaJuego(): string{
        return self::$MENSAJE_COMIENZA_JUEGO;
    }
}