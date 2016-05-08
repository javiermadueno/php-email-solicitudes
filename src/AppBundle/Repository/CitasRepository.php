<?php
/**
 * Created by PhpStorm.
 * User: jmadueno
 * Date: 04/05/2016
 * Time: 16:59
 */

namespace AppBundle\Repository;


use Doctrine\DBAL\Driver\Connection;
use AppBundle\Model\Oficina;
use Symfony\Component\Config\Definition\Exception\Exception;
use AppBundle\Util\DateUtil;

class CitasRepository
{

    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @param Connection $connection
     */
    function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function findCitasPorOficina(\DateTime $hoy)
    {

        $manana = $hoy->add(\DateInterval::createFromDateString('+1 day'));

        $citas_hoy    = $this->findCapacidadPorOficina($hoy);
        $citas_manana = $this->findCapacidadPorOficina($manana);

        $result = array_merge_recursive($citas_hoy, $citas_manana);

        return $result;


    }


    /**
     * @param \DateTime $fecha
     *
     * @return Oficina[]
     */
    public function findCapacidadPorOficina(\DateTime $fecha)
    {
        $siguiente = clone $fecha;
        $siguiente->add(\DateInterval::createFromDateString('+1 day'));

        $dia           = DateUtil::dayOfWeek($fecha);
        $dia_siguiente = DateUtil::dayOfWeek($siguiente);

        $sql = "
            SELECT
              pv.Id,
              Denominacion,
              patron_citas,
              h.Dia,
              Hora_inicio,
              Hora_fin,

              (SELECT
                COUNT(*)
              FROM
                t_cita a,
                t_estados_cita b
              WHERE a.Id = b.Id_cita
                AND b.Estado = 'Bloqueada'
                AND B.razon = 'Hora no hábil'
                AND a.Id_punto_venta = pv.Id
                AND ( CONVERT(DATE, a.Fecha) = ? OR CONVERT(DATE, a.Fecha) = ? )
                ) AS bloqueadas,

                (SELECT
                  COUNT(DISTINCT a.Id)
                FROM
                  t_cita a
                WHERE a.Id_punto_venta = pv.Id
                  AND a.Id_viajero IS NOT NULL
                  AND (CONVERT(DATE, a.Fecha) = ? OR CONVERT(DATE, a.Fecha) = ?)
                  AND a.Id NOT IN
                  (SELECT
                    c.Id
                  FROM
                    t_cita c,
                    t_estados_cita e
                  WHERE c.Id = e.Id_cita
                    AND (CONVERT(DATE, c.Fecha) = ?  OR CONVERT(DATE, c.Fecha) = ?)
                    AND (e.Estado = 'Cancelada' OR e.Estado = 'Bloqueada')
                )) as citas,

                 (SELECT
                COUNT(DISTINCT a.Fecha)
              FROM
                t_cita a,
                t_estados_cita b
              WHERE a.Id = b.Id_cita
                AND b.Estado = 'Bloqueada'
                AND B.razon <> 'Hora no hábil'
                AND a.Id_punto_venta = pv.Id
                AND ( CONVERT(DATE, a.Fecha) = ? OR CONVERT(DATE, a.Fecha) = ? )
                ) AS bloqueadas_oficina
            FROM
              t_punto_venta pv
              JOIN t_horario_punto_venta h
                ON (pv.Id = h.Id_punto_venta)
            WHERE pv.Visible = 1
              AND (h.Dia = ? OR h.Dia = ?)
            ORDER BY pv.Denominacion
        ";

        $stmt = $this->connection->prepare($sql);

        $stmt->bindValue(1, $fecha->format('Y-m-d'));
        $stmt->bindValue(2, $siguiente->format('Y-m-d'));
        $stmt->bindValue(3, $fecha->format('Y-m-d'));
        $stmt->bindValue(4, $siguiente->format('Y-m-d'));
        $stmt->bindValue(5, $fecha->format('Y-m-d'));
        $stmt->bindValue(6, $siguiente->format('Y-m-d'));
        $stmt->bindValue(7, $fecha->format('Y-m-d'));
        $stmt->bindValue(8, $siguiente->format('Y-m-d'));
        $stmt->bindValue(9, $dia);
        $stmt->bindValue(10, $dia_siguiente);

        $stmt->execute();

        $oficinas = $stmt->fetchAll();

        $resultado = [];

        foreach ($oficinas as $oficina) {

            $id = $oficina['Id'];

            if (isset($resultado[$id])) {
                $elem = $resultado[$id];

                if(!$elem instanceof Oficina) {
                  throw new Exception("El elemento con Id = {$id} no es un objeto de tipo Oficina");
                }

                $elem->addHorario($oficina);
            } else {
                $resultado[$id] = Oficina::createFromSQLArray($oficina);
            }
        }

        return $resultado;
    }

}