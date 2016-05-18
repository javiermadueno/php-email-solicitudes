<?php
/**
 * Created by PhpStorm.
 * User: jmadueno
 * Date: 04/05/2016
 * Time: 16:59
 */

namespace AppBundle\Repository;


use AppBundle\Model\Oficina;
use AppBundle\Util\DateUtil;
use Doctrine\DBAL\Driver\Connection;
use Symfony\Component\Config\Definition\Exception\Exception;

class CitasRepository
{
    const FORMATO_FECHA = 'Y-m-d';

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


    /**
     * @param \DateTimeImmutable $fecha
     *
     * @return Oficina[]
     */
    public function findCapacidadPorOficina(\DateTimeImmutable $fecha)
    {
        $siguiente = $fecha->modify('+1 day');

        $dia           = DateUtil::dayOfWeek($fecha);
        $dia_siguiente = DateUtil::dayOfWeek($siguiente);

        $sql = "
            SELECT
              pv.Id,
              Denominacion,
              patron_citas,
              h.Dia,
              CASE WHEN h.Hora_inicio < e.Hora_inicio THEN e.Hora_inicio ELSE h.Hora_inicio END AS Hora_inicio,
              CASE WHEN h.Hora_fin < e.Hora_fin THEN h.Hora_fin ELSE e.Hora_fin END AS Hora_fin,
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
                JOIN t_esquema e ON (e.Activo = 1)
            WHERE pv.Visible = 1
              AND (h.Dia = ? OR h.Dia = ?)
            ORDER BY pv.Denominacion
        ";

        $stmt = $this->connection->prepare($sql);

        $stmt->bindValue(1, $fecha->format(self::FORMATO_FECHA));
        $stmt->bindValue(2, $siguiente->format(self::FORMATO_FECHA));
        $stmt->bindValue(3, $fecha->format(self::FORMATO_FECHA));
        $stmt->bindValue(4, $siguiente->format(self::FORMATO_FECHA));
        $stmt->bindValue(5, $fecha->format(self::FORMATO_FECHA));
        $stmt->bindValue(6, $siguiente->format(self::FORMATO_FECHA));
        $stmt->bindValue(7, $fecha->format(self::FORMATO_FECHA));
        $stmt->bindValue(8, $siguiente->format(self::FORMATO_FECHA));
        $stmt->bindValue(9, $dia);
        $stmt->bindValue(10, $dia_siguiente);

        $stmt->execute();

        $oficinas = $stmt->fetchAll();

        $resultado = [];

        foreach ($oficinas as $oficina) {

            $id = $oficina['Id'];

            if (isset($resultado[$id])) {
                $elem = $resultado[$id];

                if (!$elem instanceof Oficina) {
                    throw new Exception("El elemento con Id = {$id} no es un objeto de tipo Oficina");
                }

                $elem->addHorario($oficina);
            } else {
                $resultado[$id] = Oficina::createFromSQLArray($oficina);
            }
        }

        return $resultado;
    }


    /**
     * @param \DateTimeInterface $hoy
     *
     * @return array
     */
    public function finHorasDisponibles(\DateTimeInterface $hoy)
    {
        $esquema = $this->findEsquemaActivo();

        $fechaInicio = $this->createDateInmutable($hoy, $esquema['hora_inicio']);
        $fechaFin    = $this->createDateInmutable($hoy, $esquema['hora_fin']);


        $intervalos = floor(($fechaFin->getTimestamp() - $fechaInicio->getTimestamp()) / $esquema['segundos']);
        $horas      = [];

        for ($i = 0; $i < $intervalos - 1; $i++) {
            $sec = $i * (int)$esquema['segundos'];

            if ($fechaInicio->add(new \DateInterval(sprintf("PT%sS", $sec)))->format('s') !== '00') {
                $sec += 150;
            }


            //Todo quitar el array cuando no se vaya imprimir para hacer la interseccion de horas correctamente
            $horas[] =[
                $fechaInicio
                    ->add(new \DateInterval(sprintf("PT%sS", $sec)))
                    ->format('H:i:s')
            ];
        }

        return $horas;

    }

    /**
     * @param \DateTimeInterface $fecha
     * @param  string   $hora
     *
     * @return bool|\DateTimeImmutable
     */
    private function createDateInmutable(\DateTimeInterface $fecha, $hora)
    {
        list($horas, $minutos, $segundos) = explode(':', $hora, 3);

        if($fecha instanceof \DateTime) {
            $inmutable = \DateTimeImmutable::createFromMutable($fecha);
        } else {
            $inmutable = $fecha;
        }

        return $inmutable->setTime($horas, $minutos, $segundos);
    }


    /**
     * @return array
     */
    public function findEsquemaActivo()
    {
        $sql = "
          SELECT
            Hora_Inicio as hora_inicio,
            Hora_Fin as hora_fin,
            Duracion_seg as segundos
          FROM t_esquema WHERE Activo = 1
          ";

        $stmt = $this->connection->prepare($sql);
        $stmt->execute();
        $esquema = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $esquema;
    }

}