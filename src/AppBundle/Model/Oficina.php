<?php

namespace AppBundle\Model;

/**
 * Created by PhpStorm.
 * User: jmadueno
 * Date: 05/05/2016
 * Time: 16:02
 */
class Oficina
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $nombre;

    /**
     * @var int
     */
    private $citasPorHora = 0;

    /**
     * @var array
     */
    private $horarios = [];

    /**
     * @var float
     */
    private $horas = 0;

    /**
     * @var int
     */
    private $capacidad = 0;

    /**
     * @var int
     */
    private $huecosNoHabiles = 0;

    /**
     * @var int
     */
    private $citas = 0;

    /**
     * @var int
     */
    private $bloqueadas = 0;

    /**
     * @var string
     */
    private $patronCitas;

    /**
     * @return mixed
     */
    public function getBloqueadas()
    {
        $maxBloqueadas = $this->getCapacidad() - $this->getCitas();

        return min($this->bloqueadas, $maxBloqueadas);
    }

    /**
     * @param mixed $bloqueadas
     */
    public function setBloqueadas($bloqueadas)
    {
        $this->bloqueadas = $bloqueadas;
    }

    /**
     * @return int
     */
    public function getCapacidad()
    {
        $this->capacidad = (int)($this->getCitasPorHora() * $this->getHoras());

        return $this->capacidad;
    }

    /**
     * @param mixed $capacidad
     */
    public function setCapacidad($capacidad)
    {
        $this->capacidad = $capacidad;
    }

    /**
     * @return mixed
     */
    public function getCitas()
    {
        return $this->citas;
    }

    /**
     * @param mixed $citas
     */
    public function setCitas($citas)
    {
        $this->citas = $citas;
    }

    /**
     * @return int
     */
    public function getCitasPorHora()
    {
        $this->citasPorHora =  count(explode(',', $this->patronCitas));

        return $this->citasPorHora;
    }

    /**
     * @param $patron
     *
     * @return $this
     * @throws \Exception
     */
    private function setPatronCitas($patron)
    {
        if(empty($patron)) {
            throw new \Exception("No se pueden calcular las horas sin el patrón de citas");
        }

        trim($patron, ',');

        $this->patronCitas = $patron;
        $this->getCitasPorHora();

        return $this;
    }


    /**
     * @return float|int
     */
    public function getHoras()
    {
        return $this->calculateHoras();
    }


    /**
     * @return mixed
     */
    public function getHuecosNoHabiles()
    {
        return $this->huecosNoHabiles;
    }


    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }


    /**
     * @return mixed
     */
    public function getNombre()
    {
        return $this->nombre;
    }



    /**
     * @return mixed
     */
    public function getCapacidadReal()
    {
        return $this->getCapacidad() - $this->getHuecosNoHabiles();
    }

    /**
     * @return mixed
     */
    public function getOcupacion()
    {
        $ocupados = $this->citas + $this->bloqueadas;

        return min($ocupados, $this->getCapacidadReal());
    }

    /**
     * @return float
     */
    public function getPorcentajeOcupacion()
    {
        $porcentaje = 100 * ($this->getOcupacion() / $this->getCapacidadReal());

        return (float)  round($porcentaje, 2);
    }


    /**
     * @return string
     */
    function __toString()
    {
        return (string)$this->id;
    }

    /**
     * @return array
     */
    function toArray()
    {
        return [
            'id'             => $this->getId(),
            'nombre'         => filter_var($this->getNombre(), FILTER_SANITIZE_FULL_SPECIAL_CHARS),
            'citas_hora'     => $this->getCitasPorHora(),
            'horas'          => $this->getHoras(),
            'capacidad'      => $this->getCapacidad(),
            'hueco_no_habil' => $this->getHuecosNoHabiles(),
            'capacidad_real' => $this->getCapacidadReal(),
            'citas'          => $this->getCitas(),
            'bloqueadas'     => $this->getBloqueadas(),
            'ocupacion'      => $this->getOcupacion(),
            'porcentaje_ocupacion' => $this->getPorcentajeOcupacion(),
            /**
            'horarios' => implode('; ',array_map(function($horario){
                return implode('-', $horario);
            }, $this->horarios))
             **/
        ];
    }

    /**
     * @return float|int
     */
    public function calculateHoras()
    {
        $this->horas = 0;

        $this->getCitasPorHora();

        foreach ($this->horarios as $horario) {
            $this->horas += $this
                ->calculaNumeroDeHorasEntre($horario['inicio'], $horario['fin']);
        }

        return $this->horas;
    }

    /**
     * @param $inicio
     * @param $fin
     *
     * @return float
     */
    private function calculaNumeroDeHorasEntre($inicio, $fin)
    {
        $horaInicio = new \DateTime();
        $horaFin    = new \DateTime();

        list($horas, $minutos, $segundos) = explode(':', $inicio);
        $horaInicio->setTime($horas, $minutos, $segundos);

        list($horas, $minutos, $segundos) = explode(':', $fin);
        $horaFin->setTime($horas, $minutos, $segundos);

        $totalHoras = ($horaFin->getTimestamp() - $horaInicio->getTimestamp()) / (60 * 60);

        return $totalHoras;
    }

    /**
     * @param array $oficina
     */
    public function addHorario(array $oficina)
    {
        $horario = [
            'inicio' => $oficina['Hora_inicio'],
            'fin'    => $oficina['Hora_fin']
        ];


        $this->horarios [] = $horario;

        $this->calculateHoras();
    }


    /**
     * @param array $oficina
     *
     * @return static
     */
    public static function createFromSQLArray(array $oficina)
    {
        $self                  = new static();
        $self->id              = $oficina['Id'];
        $self->nombre          = $oficina['Denominacion'];
        $self->citas           = (int)$oficina['citas'];
        $self->huecosNoHabiles = (int)$oficina['bloqueadas'];
        $self->bloqueadas      = (int)$oficina['bloqueadas_oficina'];
        $self->setPatronCitas($oficina['patron_citas']);
        $self->addHorario($oficina);

        return $self;
    }

} 