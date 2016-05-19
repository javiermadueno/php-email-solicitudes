<?php
namespace AppBundle\Util;

class DateUtil
{

    static function dayOfWeek(\DateTimeInterface $fecha)
    {
        $dia = $fecha->format('D');

        $dias = [
            'Mon' => 'L',
            'Tue' => 'M',
            'Wed' => 'X',
            'Thu' => 'J',
            'Fri' => 'V',
            'Sat' => 'S',
            'Sun' => 'D'
        ];

        return $dias[$dia];
    }

    static function diaSemanaCompleto(\DateTimeInterface $fecha)
    {
        $dia = $fecha->format('D');

        $dias = [
            'Mon' => 'Lunes',
            'Tue' => 'Martes',
            'Wed' => 'Miércoles',
            'Thu' => 'Jueves',
            'Fri' => 'Viernes',
            'Sat' => 'Sábado',
            'Sun' => 'Domingo'
        ];

        return $dias[$dia];
    }


} 