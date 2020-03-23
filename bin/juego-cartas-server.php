<?php

error_reporting(E_ERROR);

$PORT = 12345;

use Ratchet\Server\IoServer;
use SocketServer\JuegoCartasSocket;
use Ratchet\WebSocket\WsServer;
use Ratchet\Http\HttpServer;
use SocketServer\Logger;

$dir = dirname( __DIR__ ); // Devuelve el path del proyecto

require $dir . '/vendor/autoload.php';

$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            new JuegoCartasSocket()
        )
    ),
    $PORT
);

Logger::log( "Servidor websocket establecido en puerto {$PORT}" );
$server->run();
