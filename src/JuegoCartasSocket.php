<?php

namespace SocketServer;

use Exception;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use SocketServer\JuegoCartas\DatoMensaje;
use SocketServer\JuegoCartas\TipoMensajeCliente;
use SocketServer\JuegoCartas\JuegoCartas;
use SocketServer\JuegoCartas\Jugador;
use SocketServer\JuegoCartas\Mensajes;
use SocketServer\JuegoCartas\Partida;
use SocketServer\JuegoCartas\TipoMensajeServidor;

class JuegoCartasSocket implements MessageComponentInterface{

    public static $NUMERO_CARTAS = 6;
    public static $NUMERO_FOTOS = 6;
    public $clients;
    private $juegoCartas;

    public function __construct() {
        $this->clients = [];
        $this->juegoCartas = new JuegoCartas();
    }

    public function onOpen( ConnectionInterface $conn ){
        $this->clients[$conn->resourceId] = $conn;
        $msg = "Nuevo cliente! ID: {$conn->resourceId}";
        Logger::log($msg);
        //$conn->send($msg);
    }

    public function onClose( ConnectionInterface $conn ){
        unset( $this->clients[$conn->resourceId] );
        Logger::log( "Conexión cerrada por cliente {$conn->resourceId}\n" );
        $this->juegoCartas->eliminarJugador( $conn->resourceId );
    }

    public function onError( ConnectionInterface $conn, \Exception $e ){
        $idx = array_keys( $this->clients, $conn );
        unset( $this->clients[$idx] );
        Logger::log( "Ha ocurrido un error con el cliente {$conn->resourceId}: {$e->getMessage()}. Cerrando conexión." );
        $conn->close();
        $this->juegoCartas->eliminarJugador( $conn->resourceId );
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        $id = $from->resourceId;
        Logger::log( "Mensaje recibido de cliente {$id}: {$msg}\n" );
        //$from->send( 'Mensaje recibido!' );
        $datosMensaje = $this->parsearMensaje( $msg );
        if( $datosMensaje && count($datosMensaje) > 0 ){
            $tipoMensaje = $datosMensaje[ DatoMensaje::$TIPO_MENSAJE ];
            $idPartida = $datosMensaje[ DatoMensaje::$ID_PARTIDA ];
            $nombreJugador = $datosMensaje[ DatoMensaje::$NOMBRE_JUGADOR ];
            $jugador = new Jugador( $id, $nombreJugador );
            try{
                switch( $tipoMensaje ){
                    case TipoMensajeCliente::$CREAR_PARTIDA:
                        Logger::log( "CREAR PARTIDA!!" );
                        //TODO: obtener numero cartas del mensaje recibido
                        $numCartas = $datosMensaje[DatoMensaje::$NUMERO_CARTAS];
                        $this->juegoCartas->nuevaPartida( $idPartida, $jugador, $numCartas );
                        $this->enviarMensajePartidaCreada( $jugador, $idPartida );
                        break;
                    case TipoMensajeCliente::$UNIRSE_A_PARTIDA:
                        Logger::log( "UNIRSE A PARTIDA!!" );
                        $this->juegoCartas->unirseAPartida(
                            $idPartida,
                            new Jugador( $id, $nombreJugador ),
                            [ $this, 'callbackUnirseAPartida' ], // Forma de pasar un callback (tipo "callable")
                            [ $this, 'callbackComenzarJuego' ] // Forma de pasar un callback (tipo "callable")
                        );
                        break;
                    case TipoMensajeCliente::$GIRAR_CARTA:
                        Logger::log( "GIRAR CARTA!!" );
                        $idCarta = $datosMensaje[DatoMensaje::$ID_CARTA];
                        $this->enviarMensajeGiraCarta( $idPartida, $jugador, $idCarta );
                        break;
                    case TipoMensajeCliente::$NUM_CARTAS_Y_FOTOS:
                        $numCartas = $datosMensaje[DatoMensaje::$NUMERO_CARTAS];
                        $numFotos = $datosMensaje[DatoMensaje::$NUMERO_FOTOS];
                        $this->procesarNumFotosYCartas( intval($numCartas), intval($numFotos) );
                        break;
                }
            } catch( Exception $e ){
                $this->enviarMensajeError( $jugador, $e->getMessage() );
            }
        }
    }

    public function enviarMensajePartidaCreada( Jugador $jugador, string $idPartida ){
        $cliente = $this->clients[$jugador->id];
        if( $cliente ){
            $mensaje=
                DatoMensaje::$TIPO_MENSAJE . "="  . TipoMensajeServidor::$PARTIDA_CREADA . ";" .
                DatoMensaje::$ID_PARTIDA . "=" . $idPartida;
                $cliente->send( $mensaje );
        } else{
            $msg = "ERROR: no se encuentra el jugador con id {$jugador->id}";
            $this->enviarMensajeError( $jugador, $msg );    
            Logger::log( $msg );
        }
    }

    public function enviarMensajeGiraCarta( string $idPartida, Jugador $jugador, string $idCarta ){
        $partida = $this->juegoCartas->obtenerPartidas()[$idPartida];
        if( $partida ){
            $jugadorRival = null;
            if( $jugador->id === $partida->jugador1->id ){
                $jugadorRival = $partida->jugador2;
            } else if( $jugador->id === $partida->jugador2->id ){
                $jugadorRival = $partida->jugador1;
            }
            if( $jugadorRival ){
                $cliente = $this->clients[$jugadorRival->id];
                if( $cliente ){
                    $tipoMensaje = TipoMensajeServidor::$GIRA_CARTA;
                    $mensaje=
                        DatoMensaje::$TIPO_MENSAJE . "={$tipoMensaje};" .
                        DatoMensaje::$ID_CARTA . "={$idCarta}";
                    $cliente->send( $mensaje );
                } else{
                    $msg = "ERROR: ha ocurrido un error extraño 1 :S";
                    $this->enviarMensajeError( $jugador, $msg );    
                    Logger::log( $msg );
                }
            } else{
                $msg = "ERROR: ha ocurrido un error extraño 2 :S";
                $this->enviarMensajeError( $jugador, $msg );    
                Logger::log( $msg );
            }
        } else{
            $msg = "ERROR: no se encuentra la partida {$partida->idPartida}";
            $this->enviarMensajeError( $jugador, $msg );    
            Logger::log( $msg );
        }
    }

    public function enviarMensajeError( Jugador $jugador, string $mensaje ){
        $cliente = $this->clients[$jugador->id];
        $tipoMensaje = TipoMensajeServidor::$ERROR;
        $cliente->send(
            DatoMensaje::$TIPO_MENSAJE . "={$tipoMensaje};" . DatoMensaje::$MENSAJE ."={$mensaje}"
        );
    }

    public function callbackUnirseAPartida( Jugador $jugador, Jugador $jugadorUnido ){
        $tipoMensaje = TipoMensajeServidor::$JUGADOR_UNIDO;
        $msgJugadorUnido = Mensajes::getMensajeJugadorUnido( $jugadorUnido->nombre );
        $mensaje=
            DatoMensaje::$TIPO_MENSAJE . "={$tipoMensaje};" .
            DatoMensaje::$MENSAJE ."={$msgJugadorUnido};" .
            DatoMensaje::$NOMBRE_RIVAL ."={$jugadorUnido->nombre}";
        $this->clients[$jugador->id]->send( $mensaje );
    }

    public function callbackComenzarJuego( Jugador $jugador, Jugador $jugadorUnido, Partida $partida ){
        $tipoMensaje = TipoMensajeServidor::$COMIENZA_JUEGO;
        $msgComienzaJuego = Mensajes::getMensajeComienzaJuego();
        $idsCartasYNumerosStr = $this->juegoCartas->obtenerIdsCartasYNumerosStrMsg( $partida->numeroCartas, self::$NUMERO_FOTOS );
        $mensaje=
            DatoMensaje::$TIPO_MENSAJE . "={$tipoMensaje};" .
            DatoMensaje::$MENSAJE . "={$msgComienzaJuego};" .
            DatoMensaje::$CARTAS . "={$idsCartasYNumerosStr};" .
            DatoMensaje::$NUMERO_CARTAS . "={$partida->numeroCartas};";
        $turnoJ1 = random_int(0, 1) > 0 ? "1" : "0";
        $turnoJ2 = $turnoJ1 ? "0" : "1";
        $mensajeJugador = $mensaje . DatoMensaje::$TURNO . "={$turnoJ1};";
        $mensajeJugador = $mensajeJugador . DatoMensaje::$NOMBRE_RIVAL . "=" . $jugadorUnido->nombre;
        $mensajeJugadorUnido = $mensaje . DatoMensaje::$TURNO . "={$turnoJ2};";
        $mensajeJugadorUnido = $mensajeJugadorUnido . DatoMensaje::$NOMBRE_RIVAL . "=" . $jugador->nombre;
        echo $mensajeJugador . "\n";
        echo $mensajeJugadorUnido . "\n";
        $this->clients[$jugador->id]->send( $mensajeJugador );
        $this->clients[$jugadorUnido->id]->send( $mensajeJugadorUnido );
    }

    /**
     * @param $mensaje string de elementos clave-valor separados por ";". La clave
     * y el valor quedan separados por un "="
     */
    private function parsearMensaje( string $mensaje ): array{
        $arrDatos = [];
        if( $mensaje ){
            $datosMensaje = explode( ";", $mensaje );
            if( $datosMensaje && count($datosMensaje) > 0 ){
                foreach( $datosMensaje as $dato ){
                    $claveValor = explode( "=", $dato );
                    if( $claveValor && count( $claveValor ) > 1 ){
                        $arrDatos[$claveValor[0]] = $claveValor[1];
                    }
                }
            }
        }
        return $arrDatos;
    }

    private static function procesarNumFotosYCartas( int $numCartas, int $numFotos ){
        if( $numCartas != self::$NUMERO_CARTAS ){
            self::$NUMERO_CARTAS = $numCartas;
        }
        if( $numFotos != self::$NUMERO_FOTOS ){
            self::$NUMERO_FOTOS = $numFotos;
        }
    }

}