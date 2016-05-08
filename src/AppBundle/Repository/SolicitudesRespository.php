<?php
/**
 * Created by PhpStorm.
 * User: jmadueno
 * Date: 04/05/2016
 * Time: 15:45
 */

namespace AppBundle\Repository;

use Doctrine\DBAL\Driver\Connection;


class SolicitudesRespository
{

    const FORMATO_FECHA = 'Y-m-d';
    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @param Connection $connection
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }


    /**
     * @param \DateTime $desde
     * @param \Datetime $hoy
     *
     * @return array
     */
    public function findTotalSolicitudesCompletadas(\DateTime $desde, \Datetime $hoy)
    {
        $normales = $this->findTotalSolicitudesNormale($desde, $hoy);
        $duplicado = $this->findTotalSolicitudesDuplicadas($desde, $hoy);
        $infantil = $this->findTotalSolicitudesInfantil($desde, $hoy);

        return array_merge($normales, $duplicado, $infantil);
    }


    /**
     * @param \DateTime $desde
     * @param \DateTime $hoy
     *
     * @return array
     */
    protected  function findTotalSolicitudesNormale(\DateTime $desde, \DateTime $hoy)
    {
        $sql = "
            SELECT
              LEFT( z.tipo_abono, 1)+ lower(RIGHT( z.tipo_abono, len( z.tipo_abono)-1) ) as tipo,
              --z.tipo_abono  as tipo,
              COUNT(*) solicitudes
            FROM
              (SELECT DISTINCT
                a.Tipo,
                a.Id,
                a.Fecha_peticion,
                a.tipo_abono
              FROM
                t_solicitud a,
                t_estados_solicitud b
              WHERE a.Id = b.Id_solicitud
                AND b.Estado = 'SOLICITUD COMPLETADA'
                AND a.Fecha_peticion >= ?
                AND a.Fecha_peticion < ?
                AND a.id_usuario_digitalizador IS NULL
                AND a.Id NOT IN
                (SELECT
                  x.Id
                FROM
                  t_solicitud X,
                  t_estados_solicitud Y
                WHERE x.Id = y.Id_solicitud
                  AND y.Estado = 'ANULADA'
                  AND x.Fecha_peticion >= ?
                  AND x.Fecha_peticion < ?)) z
            GROUP BY
              z.tipo_abono
            ORDER BY z.tipo_abono
        ";

        $stmt = $this->connection->prepare($sql);

        $stmt->bindValue(1, $desde->format(self::FORMATO_FECHA));
        $stmt->bindValue(2, $hoy->format(self::FORMATO_FECHA));
        $stmt->bindValue(3, $desde->format(self::FORMATO_FECHA));
        $stmt->bindValue(4, $hoy->format(self::FORMATO_FECHA));

        $stmt->execute();

        $solicitudes = $stmt->fetchAll();

        return $solicitudes;
    }


    /**
     * @param \DateTime $desde
     * @param \Datetime $hoy
     *
     * @return array
     */
    protected function findTotalSolicitudesDuplicadas(\DateTime $desde, \Datetime $hoy)
    {
        $sql = "
            SELECT
              'Por Extravio'  as tipo ,
              COUNT(*) solicitudes
            FROM
              (SELECT DISTINCT
                a.Id,
                a.Fecha_peticion
              FROM
                t_solicitud_duplicado a,
                t_estados_solicitud_duplicado b
              WHERE a.Id = b.Id_solicitud
                AND b.Estado = 'SOLICITUD COMPLETADA'
                AND a.Fecha_peticion >= ?
                AND a.Fecha_peticion < ?
                AND a.Id NOT IN
                (SELECT
                  x.Id
                FROM
                  t_solicitud_duplicado X,
                  t_estados_solicitud_duplicado Y
                WHERE x.Id = y.Id_solicitud
                  AND y.Estado = 'ANULADA'
                  AND x.Fecha_peticion >= ?
                  AND x.Fecha_peticion < ?
                )
              ) z
        ";


        $stmt = $this->connection->prepare($sql);

        $stmt->bindValue(1, $desde->format(self::FORMATO_FECHA));
        $stmt->bindValue(2, $hoy->format(self::FORMATO_FECHA));
        $stmt->bindValue(3, $desde->format(self::FORMATO_FECHA));
        $stmt->bindValue(4, $hoy->format(self::FORMATO_FECHA));


        $stmt->execute();

        $solicitudes = $stmt->fetchAll();

        return $solicitudes;

    }

    /**
     * @param \DateTime $desde
     * @param \Datetime $hoy
     *
     * @return array
     */
    protected function findTotalSolicitudesInfantil(\DateTime $desde, \Datetime $hoy = null)
    {
        $sql ="
            SELECT
              'Infantil' as tipo,
              COUNT(*) solicitudes
            FROM
              (SELECT DISTINCT
                a.Id,
                a.Fecha_peticion
              FROM
                t_solicitud_infantil a,
                t_estados_solicitud_infantil b
              WHERE a.Id = b.Id_solicitud
                AND b.Estado = 'SOLICITUD COMPLETADA'
                AND a.Fecha_peticion >= ?
                AND a.Fecha_peticion < ?
                AND a.Id NOT IN
                (SELECT
                  x.Id
                FROM
                  t_solicitud_infantil X,
                  t_estados_solicitud_infantil Y
                WHERE x.Id = y.Id_solicitud
                  AND y.Estado = 'ANULADA'
                  AND x.Fecha_peticion >= ?
                  AND x.Fecha_peticion < ?)) z
        ";

        $stmt = $this->connection->prepare($sql);

        $stmt->bindValue(1, $desde->format(self::FORMATO_FECHA));
        $stmt->bindValue(2, $hoy->format(self::FORMATO_FECHA));
        $stmt->bindValue(3, $desde->format(self::FORMATO_FECHA));
        $stmt->bindValue(4, $hoy->format(self::FORMATO_FECHA));


        $stmt->execute();

        $solicitudes = $stmt->fetchAll();

        return $solicitudes;

    }



} 