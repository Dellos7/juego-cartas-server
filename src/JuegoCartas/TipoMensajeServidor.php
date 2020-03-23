<?php

namespace SocketServer\JuegoCartas;

class TipoMensajeServidor{

    public static $ERROR = 'error';
    public static $COMIENZA_JUEGO = 'comienza_juego';
    public static $JUGADOR_UNIDO = 'jugador_unido';
    public static $GIRA_CARTA = 'gira_carta';
    public static $PARTIDA_CREADA = 'partida_creada';
}