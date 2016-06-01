<?php
/**
 * Created by PhpStorm.
 * User: jmadueno
 * Date: 01/06/2016
 * Time: 12:04
 */

namespace AppBundle\Repository;


abstract class DateFunctionalityRepository
{
    static $FORMATO_FECHA;

    public function __construct($formatoFecha = 'Y-m-d') {
        self::$FORMATO_FECHA = $formatoFecha;
    }

} 