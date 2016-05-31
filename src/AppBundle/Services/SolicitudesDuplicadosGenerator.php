<?php
/**
 * Created by PhpStorm.
 * User: jmadueno
 * Date: 31/05/2016
 * Time: 14:00
 */

namespace AppBundle\Services;


use AppBundle\Repository\SolicitudesRespository;

class SolicitudesDuplicadosGenerator
{
    protected $generator;

    protected $repository;


    protected $fields = [
        'SOLICITUD',
        'FECHA',
        'DOCUMENTO PERSONAL',
        'TIPO DOCUMENTO',
        'NOMBRE',
        'APELLIDO1',
        'APELLIDO2',
        'TIPO VIA',
        'NOMBRE VIA',
        'NUMERO',
        'PORTAL',
        'ESCALERA',
        'PISO',
        'PUERTA',
        'LOCALIDAD',
        'PROVINCIA',
        'CODIGO POSTAL',
        'TELEFONO1',
        'TELEFONO2',
        'CORREO ELECTRONICO',
        'TRANSFERENCIA',
        'ESTADO'
    ];

    public function __construct(SolicitudesRespository $repository, ExcelGenerator $generator)
    {
        $this->generator = $generator;
        $this->repository = $repository;
    }

    /**
     * @return \SplFileObject
     */
    public function generate()
    {
        $data = $this->repository->findSolicitudesDuplicados();

        $hoy = new \DateTimeImmutable('now');
        $nombreFichero = sprintf('Solicitudes Duplicado %s.xlsx', $hoy->format('Ymd'));

        return $this->generator->generate($data, $this->fields, $nombreFichero);
    }

} 