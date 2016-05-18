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


} 