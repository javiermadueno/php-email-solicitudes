<?php
/**
 * Created by PhpStorm.
 * User: jmadueno
 * Date: 31/05/2016
 * Time: 15:39
 */

namespace AppBundle\Services;

use AppBundle\Repository\SolicitudesRespository;
use AppBundle\Services\ExcelGenerator;


class SolicitudesGenerator
{
    protected $generator;

    protected $repository;


    protected $fields = [
        'Solicitud',
        'Tipo',
        'Tipo abono',
        'Estado',
        'Documento',
        'Fecha',
        'Nombre',
        'Apellido 1',
        'Apellido 2',
        'Fecha Nacimiento',
        'Correo Electrónico',
        'Teléfono 1',
        'Teléfono 2',
        'Envío',
        'Tipo vía',
        'Nombre vía',
        'Número',
        'Portal',
        'Escalera',
        'Piso',
        'Puerta',
        'Provincia',
        'Localidad',
        'CP',
        'Club de amigos',
        'Nombre Completo',
        'Vía',
        'Dirección completa',
        'CP Municipio',
        'Familia Numerosa',
        'Discapacidad',
        'Enviado Logista',
        'Fecha de realización',
        'Fecha de entrega',
        'Fecha de recogida',
        'Fecha cancelación duplicidad',
        'Fecha cancelación otro canal',
        'Fecha cancelación usuario'
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
        $data = $this->repository->findListadoSolicitudesNormales();

        $hoy = new \DateTimeImmutable('now');
        $nombreFichero = sprintf('Solicitudes sistema %s.xlsx', $hoy->format('Ymd'));

        return $this->generator->generate($data, $this->fields, $nombreFichero);
    }


} 