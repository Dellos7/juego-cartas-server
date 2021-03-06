<?php

namespace SocketServer\JuegoCartas;

use SocketServer\JuegoCartas\Excepciones\PartidaNoExisteException;
use SocketServer\JuegoCartas\Excepciones\PartidaYaExisteException;
use SocketServer\JuegoCartas\Excepciones\JugadorNoListoException;

class JuegoCartas{

    private $partidas;

    public function __construct() {
        $this->partidas = [];
    }

    public function nuevaPartida( string $idPartida, Jugador $jugador, int $numeroCartas ){
        $existe = array_key_exists( $idPartida, $this->partidas );
        if( $existe ){
            throw new PartidaYaExisteException();
        }
        $partida = new Partida($idPartida);
        $partida->jugador1 = $jugador;
        $partida->numeroCartas = $numeroCartas;
        $this->partidas[$idPartida] = $partida;
    }

    public function unirseAPartida( string $idPartida, Jugador $jugador, callable $fnCallbackUnirse, callable $fnCallbackComenzarJuego ){
        $partida = $this->partidas[$idPartida];
        if( !$partida ){
            throw new PartidaNoExisteException();
        }
        if( $partida->jugador1 && !$partida->jugador2 ){
            $partida->jugador2 = $jugador; // Jugador se une como jugador2
            $fnCallbackUnirse( $partida->jugador1, $partida->jugador2 ); // Enviaremos un mensaje al otro jugador
            $fnCallbackComenzarJuego( $partida->jugador1, $partida->jugador2, $partida );
        } else if( $partida->jugador2 && !$partida->jugador1 ){
            $partida->jugador1 = $jugador; // Jugador se une como jugador 1
            $fnCallbackUnirse( $partida->jugador2, $partida->jugador1 ); // Enviaremos un mensaje al otro jugador
            $fnCallbackComenzarJuego( $partida->jugador2, $partida->jugador1, $partida );
        } else if( $partida->jugador1 && $partida->jugador2 ){
            //TODO: 2 jugadores ya en la partida, hacer algo
        } else{
            $fnCallbackUnirse(); // No hay otro jugador en la partida, aunque está creada
        }
    }

    public function comenzarJuego( string $idPartida, callable $fnCallback ){
        $partida = $this->partidas[$idPartida];
        if( !$partida->jugador1 || $partida->jugador2 ){
            throw new JugadorNoListoException();
        }
        // Enviaremos un mensaje a los 2 jugadores y les dejaremos hacer click
        $fnCallback( $partida->jugador1->id, $partida->jugador1->id );
    }

    public function eliminarJugador( string $idJugador ){
        // Eliminamos al jugador de todas las partidas en las que esté
        $partidasAEliminarIdx = [];
        foreach( $this->partidas as $idxPartida=>$partida ){
            if( $partida->jugador1->id == $idJugador ){
                $partida->jugador1 = null;
            }
            if( $partida->jugador2->id == $idJugador ){
                $partida->jugador2 = null;
            }
            // La partida se queda vacía, la eliminaremos
            if( !$partida->jugador1 && !$partida->jugador2 ){
                $partidasAEliminarIdx[$idxPartida] = $idxPartida;
            }
        }
        // Eliminamos todas las partidas vacías
        foreach( $partidasAEliminarIdx as $idx ){
            unset( $this->partidas[$idx] );
        }
    }

    public function obtenerPartidas(): array {
        return $this->partidas;
    }

    public function obtenerIdsCartasYNumeros( int $numeroCartas, int $numeroFotos ): array{
        $nums = [];
        $ids = [];
        $i = 0;
        while( $i < $numeroCartas ){
            $rand = -1;
            $id1 = -1;
            $id2 = -1;
            do{
                $rand = random_int( 1, $numeroFotos );
                $id1 = random_int( 1, 1000 );
                $id2 = random_int( 1, 1000 );
            } while( $nums[$rand] || $ids[$id1] || $ids[$id2] || ($id1 === $id2) );
            $nums[$rand] = true;
            $ids[$id1] = $rand;
            $ids[$id2] = $rand;
            $i++;
        }
        return $ids;
    }

    public function obtenerIdsCartasYNumerosStrMsg( int $numeroCartas, int $numeroFotos ): string{
        $idsYNums = $this->obtenerIdsCartasYNumeros( $numeroCartas, $numeroFotos );
        $strMsg = '';
        $i = 0;
        foreach( $idsYNums as $id=>$num ){
            $strMsg .= $id . '-' . $num . ( ++$i < count($idsYNums) ? '_' : '' );
        }
        return $strMsg;
    }

}