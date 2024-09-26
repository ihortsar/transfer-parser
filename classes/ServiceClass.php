<?php

class ServiceClass
{
    public static $serviceTypes = [
        'Private Standard Car' => 'PE',
        'VIP Service' => 'VIP',
        'Sport Utility Vehicle' => 'SUV',
        'Luxury Car' => 'LX',
        'Shuttle' => 'SH',
        'Minibus' => 'MB',
        'Extra Large' => 'XL',
        'Economy Class' => 'EC',
        'Business Class' => 'BC',
        'Transfer Service' => 'TR',
        'Medium car' => 'MC',
        'Private car'=> 'PC'
    ];

    public static function getServiceCode($serviceName)
    {
        return self::$serviceTypes[$serviceName] ?? "";
    }

}
