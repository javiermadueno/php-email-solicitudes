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

    protected $fechaInicioNormal;

    protected $fechaInicioJoven;

    protected  $fechaInicioTerceraEdad;

    protected  $fechaInicioDuplicados;

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
        $this->fechaInicioJoven = new \DateTimeImmutable('2013-10-22');
        $this->fechaInicioNormal =  new \DateTimeImmutable('2014-05-13');
        $this->fechaInicioTerceraEdad = new \DateTimeImmutable('2014-06-15');
        $this->fechaInicioDuplicados = new \DateTimeImmutable('2015-01-07');
    }


    /**
     * @param \DateTimeInterface $desde
     * @param \DateTimeInterface $hoy
     *
     * @return array
     */
    public function findTotalSolicitudesCompletadas(\DateTimeInterface $desde, \DateTimeInterface $hoy)
    {
        $normales  = $this->findTotalSolicitudesNormale($desde, $hoy);
        $duplicado = $this->findTotalSolicitudesDuplicadas($desde, $hoy);
        $infantil  = $this->findTotalSolicitudesInfantil($desde, $hoy);

        return array_merge($normales, $duplicado, $infantil);
    }


    /**
     * @param \DateTimeInterface $desde
     * @param \DateTimeInterface $hoy
     *
     * @return array
     */
    protected function findTotalSolicitudesNormale(\DateTimeInterface $desde, \DateTimeInterface $hoy)
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
     * @param \DateTimeInterface $desde
     * @param \DateTimeInterface $hoy
     *
     * @return array
     */
    protected function findTotalSolicitudesDuplicadas(\DateTimeInterface $desde, \DateTimeInterface $hoy)
    {
        $sql = "
            SELECT
              'Por Extravío'  as tipo ,
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
     * @param \DateTimeInterface $desde
     * @param \DateTimeInterface $hoy
     *
     * @return array
     */
    protected function findTotalSolicitudesInfantil(\DateTimeInterface $desde, \DateTimeInterface $hoy = null)
    {
        $sql = "
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

    /**
     * @param \DateTimeInterface $hoy
     *
     * @return array
     */
    public function findAcumuladoSolicitudesHasta(\DateTimeInterface $hoy)
    {
        $joven       = $this->findAcumuladoSolicitudesJoven($hoy);
        $normal      = $this->findAcumuladoSolicitudesNormales($hoy);
        $terceraEdad = $this->findAcumuladoSolicitudesTerceraEdad($hoy);
        $duplicados  = $this->findAcumuladorSolicitudesDuplicado($hoy);
        $infantil    = $this->findAcumuladoSolicitudesInfantil($hoy);


        return array_merge($joven, $normal, $terceraEdad, $duplicados, $infantil);
    }

    /**
     * @param \DateTimeInterface $hoy
     *
     * @return array
     */
    protected function findAcumuladoSolicitudesNormales(\DateTimeInterface $hoy)
    {
        $sql = "
            SELECT
              z.Tipo,
              z.tipo_abono,
              COUNT(*) solicitudes
            FROM
              (SELECT DISTINCT
                a.Tipo,
                a.Id,
                a.Fecha_peticion,
                'NORMAL' AS tipo_abono
              FROM
                t_solicitud a,
                t_estados_solicitud b
              WHERE a.Id = b.Id_solicitud
                AND (
                  a.tipo_abono = 'NORMAL_B O C'
                  OR a.tipo_abono = 'NORMAL'
                )
                AND b.Estado = 'SOLICITUD COMPLETADA'
                AND a.Fecha_peticion >= '{$this->fechaInicioNormal->format(self::FORMATO_FECHA)}' -- Fecha inicio Normal B o C
                AND a.Fecha_peticion < ?
                AND a.id_usuario_digitalizador IS NULL -- Añadido nuevo el 28/07/2014 para exculuir las realizadas con digitalizador
                AND a.Id NOT IN
                (SELECT
                  x.Id
                FROM
                  t_solicitud X,
                  t_estados_solicitud Y
                WHERE x.Id = y.Id_solicitud
                  AND y.Estado = 'ANULADA'
                  AND x.Fecha_peticion >= '{$this->fechaInicioNormal->format(self::FORMATO_FECHA)}' -- Fecha inicio Normal B o C
                  AND x.Fecha_peticion < ?)) z
            GROUP BY z.Tipo,
              z.tipo_abono
            ORDER BY z.tipo_abono,
              z.Tipo
        ";


        $stmt = $this->connection->prepare($sql);

        $stmt->bindValue(1, $hoy->format(self::FORMATO_FECHA));
        $stmt->bindValue(2, $hoy->format(self::FORMATO_FECHA));

        $stmt->execute();

        $solicitudes = array();

        return array_reduce($stmt->fetchAll(), function($solicitudes, $solicitud){

            $tipo = $solicitud['Tipo'];

            $tipo = strpos($tipo, 'NUEVA') !== false ? 'nuevas' : 'renovacion';

            $solicitudes['normal'][$tipo] = $solicitud['solicitudes'];
            return $solicitudes;
        }, $solicitudes);


    }

    /**
     * @param \DateTimeInterface $hoy
     *
     * @return array
     */
    protected function findAcumuladoSolicitudesJoven(\DateTimeInterface $hoy)
    {
        $sql = "
            SELECT
              z.Tipo,
              z.tipo_abono,
              COUNT(*) solicitudes
            FROM
              (SELECT DISTINCT
                a.Tipo,
                a.Id,
                a.Fecha_peticion,
                'JOVEN' AS tipo_abono
              FROM
                t_solicitud a,
                t_estados_solicitud b
              WHERE a.Id = b.Id_solicitud
                AND (
                  a.tipo_abono = 'Joven_B'
                  OR a.tipo_abono = 'JOVEN_B O C'
                  OR a.tipo_abono = 'JOVEN'
                )
                AND b.Estado = 'SOLICITUD COMPLETADA'
                AND a.Fecha_peticion >= '{$this->fechaInicioJoven->format(self::FORMATO_FECHA)}' -- Fecha inicio Joven B
                AND a.Fecha_peticion < ?
                AND a.id_usuario_digitalizador IS NULL -- Añadido nuevo el 28/07/2014 para exculuir las realizadas con digitalizador
                AND a.Id NOT IN
                (SELECT
                  x.Id
                FROM
                  t_solicitud X,
                  t_estados_solicitud Y
                WHERE x.Id = y.Id_solicitud
                  AND y.Estado = 'ANULADA'
                  AND x.Fecha_peticion >= '{$this->fechaInicioJoven->format(self::FORMATO_FECHA)}' -- Fecha inicio Joven B
                  AND x.Fecha_peticion < ? )) z
            GROUP BY z.Tipo,
              z.tipo_abono
            ORDER BY z.tipo_abono,
              z.Tipo
        ";

        $stmt = $this->connection->prepare($sql);

        $stmt->bindValue(1, $hoy->format(self::FORMATO_FECHA));
        $stmt->bindValue(2, $hoy->format(self::FORMATO_FECHA));

        $stmt->execute();

         $solicitudes = array();

        return array_reduce($stmt->fetchAll(), function($solicitudes, $solicitud){

            $tipo = $solicitud['Tipo'];

            $tipo = strpos($tipo, 'NUEVA') !== false ? 'nuevas' : 'renovacion';

            $solicitudes['joven'][$tipo] = $solicitud['solicitudes'];
            return $solicitudes;
        }, $solicitudes);
    }


    /**
     * @param \DateTimeInterface $hoy
     *
     * @return array
     */
    protected function findAcumuladoSolicitudesTerceraEdad(\DateTimeInterface $hoy)
    {
        $sql = "
            SELECT
              z.Tipo,
              z.tipo_abono,
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
                AND a.tipo_abono = 'TERCERA EDAD_TE'
                AND b.Estado = 'SOLICITUD COMPLETADA'
                AND a.Fecha_peticion >= '{$this->fechaInicioTerceraEdad->format(self::FORMATO_FECHA)}' -- Fecha inicio Tercera Edad
                AND a.Fecha_peticion < ?
                AND a.id_usuario_digitalizador IS NULL -- Añadido nuevo el 28/07/2014 para exculuir las realizadas con digitalizador
                AND a.Id NOT IN
                (SELECT
                  x.Id
                FROM
                  t_solicitud X,
                  t_estados_solicitud Y
                WHERE x.Id = y.Id_solicitud
                  AND y.Estado = 'ANULADA'
                  AND x.Fecha_peticion >= '{$this->fechaInicioTerceraEdad->format(self::FORMATO_FECHA)}' -- Fecha inicio Tercera Edad
                  AND x.Fecha_peticion < ? )) z
            GROUP BY z.Tipo,
              z.tipo_abono
            ORDER BY z.tipo_abono,
              z.Tipo
        ";

        $stmt = $this->connection->prepare($sql);

        $stmt->bindValue(1, $hoy->format(self::FORMATO_FECHA));
        $stmt->bindValue(2, $hoy->format(self::FORMATO_FECHA));

        $stmt->execute();

        $solicitudes = array();

        return array_reduce($stmt->fetchAll(), function($solicitudes, $solicitud){

            $tipo = $solicitud['Tipo'];
            $tipo = strpos($tipo, 'NUEVA') !== false ? 'nuevas' : 'renovacion';
            $solicitudes['tercera_edad'][$tipo] = $solicitud['solicitudes'];

            return $solicitudes;
        }, $solicitudes);
    }

    /**
     * @param \DateTimeInterface $hoy
     *
     * @return array
     */
    protected function findAcumuladorSolicitudesDuplicado(\DateTimeInterface $hoy)
    {
        $sql = "
            SELECT
              'ACUMULADO DUPLICADAS',
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
                AND a.Fecha_peticion >= '{$this->fechaInicioDuplicados->format(self::FORMATO_FECHA)}' -- Fecha Inicio Duplicados
                AND a.Fecha_peticion < ?
                AND a.Id NOT IN
                (SELECT
                  x.Id
                FROM
                  t_solicitud_duplicado X,
                  t_estados_solicitud_duplicado Y
                WHERE x.Id = y.Id_solicitud
                  AND y.Estado = 'ANULADA'
                  AND x.Fecha_peticion >= '{$this->fechaInicioDuplicados->format(self::FORMATO_FECHA)}' -- Fecha Inicio Duplicados
                  AND x.Fecha_peticion < ?
                  )
              ) z
        ";

        $stmt = $this->connection->prepare($sql);

        $stmt->bindValue(1, $hoy->format(self::FORMATO_FECHA));
        $stmt->bindValue(2, $hoy->format(self::FORMATO_FECHA));

        $stmt->execute();

        $solicitudes = array();

        return array_reduce($stmt->fetchAll(), function($solicitudes, $solicitud){
            $solicitudes['duplicados']['nuevas'] = $solicitud['solicitudes'];
            return $solicitudes;
        }, $solicitudes);
    }


    /**
     * @param \DateTimeInterface $hoy
     *
     * @return array
     */
    protected function findAcumuladoSolicitudesInfantil(\DateTimeInterface $hoy)
    {
        $sql = "
            SELECT
              'ACUMULADO INFANTILES',
              COUNT(*) solicitudes
            FROM
              (SELECT DISTINCT
                a.Id,
                a.Fecha_peticion
              FROM
                t_solicitud_infantil a,
                t_estados_solicitud_infantil b
              WHERE a.Id = b.Id_solicitud
                AND b.Estado = 'SOLICITUD COMPLETADA' -- and a.Fecha_peticion >= '2015-07-01 00:00:00.000' - - - - Fecha Inicio Infantil
                AND a.Fecha_peticion < ?
                AND a.Id NOT IN
                (SELECT
                  x.Id
                FROM
                  t_solicitud_infantil X,
                  t_estados_solicitud_infantil Y
                WHERE x.Id = y.Id_solicitud
                  AND y.Estado = 'ANULADA' -- and x.Fecha_peticion >= '2015-07-01 00:00:00.000' - - - - Fecha Inicio Infantil
                  AND x.Fecha_peticion < ?)) z
        ";

        $stmt = $this->connection->prepare($sql);

        $stmt->bindValue(1, $hoy->format(self::FORMATO_FECHA));
        $stmt->bindValue(2, $hoy->format(self::FORMATO_FECHA));

        $stmt->execute();

        $solicitudes = array();

        return array_reduce($stmt->fetchAll(), function($solicitudes, $solicitud){
            $solicitudes['infantil']['nuevas'] = $solicitud['solicitudes'];
            return $solicitudes;
        }, $solicitudes);
    }

    public function findListadoSolicitudesNormales()
    {
        $hoy = new \DateTimeImmutable('now');

        $sql = file_get_contents(__DIR__.'/../../../queries/solicitudes_mes.sql');

        $stmt = $this->connection->prepare($sql);

        $hoy =         $hoy->modify('first day of this month');
        $mesAnterior = $hoy->modify('first day of previous month');


        $stmt->bindValue(1, $mesAnterior->format(self::FORMATO_FECHA));
        $stmt->bindValue(2, $hoy->format(self::FORMATO_FECHA));
        $stmt->bindValue(3, $hoy->format(self::FORMATO_FECHA));
        $stmt->bindValue(4, $mesAnterior->format(self::FORMATO_FECHA));
        $stmt->bindValue(5, $hoy->format(self::FORMATO_FECHA));
        $stmt->bindValue(6, $hoy->format(self::FORMATO_FECHA));

        try {
            $stmt->execute();
        } catch(\PDOException $e) {
            return [];
        }


        $solicitudes = $stmt->fetchAll();

        return $solicitudes;
    }


    public function findSolicitudesDuplicados()
    {
        $hoy = new \DateTimeImmutable('now');

        $sql = file_get_contents(__DIR__.'/../../../queries/duplicados.sql');

        $stmt = $this->connection->prepare($sql);

        $hoy =         $hoy->modify('first day of this month');
        $mesAnterior = $hoy->modify('first day of previous month');

        $stmt->bindValue(1, $mesAnterior->format(self::FORMATO_FECHA));

        try {
            $stmt->execute();
        } catch(\PDOException $e) {
            return [];
        }

        $solicitudes = $stmt->fetchAll();

        return $solicitudes;

    }


}