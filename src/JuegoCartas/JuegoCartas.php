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

    public function nuevaPartida( string $idPartida, Jugador $jugador ){
        $existe = array_key_exists( $idPartida, $this->partidas );
        if( $existe ){
            throw new PartidaYaExisteException();
        }
        $partida = new Partida($idPartida);
        $partida->jugador1 = $jugador;
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
            $fnCallbackComenzarJuego( $partida->jugador1, $partida->jugador2 );
        } else if( $partida->jugador2 && !$partida->jugador1 ){
            $partida->jugador1 = $jugador; // Jugador se une como jugador 1
            $fnCallbackUnirse( $partida->jugador2, $partida->jugador1 ); // Enviaremos un mensaje al otro jugador
            $fnCallbackComenzarJuego( $partida->jugador2, $partida->jugador1 );
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

    /*public function obtenerIdsCartasYNumeros( int $numeroCartas ): array{
        $ids = [];
        $nums = [];
        while( count($nums) < $numeroCartas ){
            $rndId = random_int( 1, 1000 );
            $rndNum = random_int( 1, $numeroCartas );
            if( !$ids[$rndId] && !$nums[$rndNum] ){
                $ids[$rndId] = $rndNum;
                $nums[$rndNum] = $rndNum;
            }
        }
        return $ids;
    }*/

    public function obtenerIdsCartasYNumeros( int $numeroCartas, int $numeroFotos ): array{
        $ids = [];
        $nums = [];
        while( count($ids) < ($numeroCartas*2) ){
            $rndId = random_int( 1, 1000 );
            $rndNum = random_int( 1, $numeroFotos );
            if( !$ids[$rndId] && $this->numVecesEnArr($rndNum, $nums) < 2 ){
                $ids[$rndId] = $rndNum;
                $nums[] = $rndNum;
            }
        }
        return $ids;
    }

    private function numVecesEnArr( $elem, $arr ): int{
        $num = 0;
        foreach( $arr as $e ){
            if( $e == $elem ){
                $num++;
            }
        }
        return $num;
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