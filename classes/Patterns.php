<?php

class Patterns
{

    public static $patternsBookings = [
        'mainContact' => '/Name:\s*([a-zÀ-Ÿ0-9\s,.\'-]+?)(?=TO\.Ref)/i',
        'phone' => '/mobile\s*contact:\s*([0-9]+)/i',
        'bookingConf' => '/-+\s*(\w+)\s*-+/i ',
        'arrival' => '/Arrival:\s*([0-9a-z,-:]+)/i',
        'departure' => '/Departure:\s*([0-9a-z,-:]+)/i',
        'serviceID' => '/SERVICE\s*ID:\s*([0-9-]+)/',
        'serviceClass' => '/Commercial\s*description:\s*([a-z\s*]+?)(?=\s*Serv)/i',
        'paxTotal' => '/Paxes:\s*([0-9]+?)(?=\s*\()/i',
        'fromTransfer' => '/From\s*:\s*([a-zÀ-Ÿ0-9\s,:.-]+?)(?=Pickup)/i',
        'pickupDate' => '/Up\s*Time\s*:\s*([0-9\/]+)/i',
        'pickupTime' => '/Up\s*Time\s*:\s*[0-9\/]+\s*([0-9:]+)/i',
        'flightNumber' => '/Transport:\s*([A-Z]{1,5}\s*[0-9]{1,9})/',
        'flightDepartTime' => '/Time\s*of\s*Depart.:\s*([0-9\:]+?)(?=\s*hrs)/i',
        'flightArrivalTime' => '/Arrival\s*Time\s*:\s*([0-9\:]+?)(?=\s*hrs)/i',
        'flightOrigin' => '/hrs\s*From:\s*([A-Z]{3})/',
        'flightDestination' => '/To:\s*([A-Z]{3})/',
        'caseInvoice' => '/SERVICE ID:\s*([0-9-]+)-/',
        'remarks' => '/AD\+([0-9]+)/i',
        'toTransfer' => '/To\s*:\s*([a-zÀ-Ÿ0-9\s,.:-]+?)(?=\s*Transport|\s*From:|\s*$|\s*Page)/i',
        'vehicleType' => '/Vehicle\s*:\s*([a-z\s]+? )(?=\s*Paxes)/i',
        'bookingType' => '/-{2,}\s*([a-z]+)\s*-{2,}/i',
    ];


    public static $patternsHeader = [
        'contactPerson' => '/^\s*([a-z]+)/i',
        'email' => '/email\s*:\s*([a-zÀ-Ÿ0-9\@.-_]+?)(?=\s*supplier)/i',
        'phoneArranger' => '/Tel\s*:\s*\(\+([0-9]+)\)\s*([0-9]+)/i',


    ];


    public static $citiesVariations = [
        'aachen' => 'Aachen',
        'augsburg' => 'Augsburg',
        'berlin' => 'Berlin',
        'düsseldorf' => 'Düsseldorf',
        'duesseldorf' => 'Düsseldorf',
        'dusseldorf' => 'Düsseldorf',
        'dresden' => 'Dresden',
        'essen' => 'Essen',
        'frankfurt' => 'Frankfurt',
        'hanover' => 'Hannover',
        'köln' => 'Köln',
        'koeln' => 'Köln',
        'leipzig' => 'Leipzig',
        'münchen' => 'München',
        'muenchen' => 'München',
        'nuernberg' => 'Nürnberg',
        'nuremberg' => 'Nürnberg',
        'stuttgart' => 'Stuttgart',
        'würzburg' => 'Würzburg',
        'wuerzburg' => 'Würzburg',
    ];


    public static function removeUnnesecerySpaces($rawMatchesString)
    {
        return preg_replace('/\s+/', ' ', $rawMatchesString);
    }


    public static function editPhoneNumber($phoneNumber)
    {
        $cleanedMatch = preg_replace('/\s+/', ' ', $phoneNumber);
        $pref1 = substr($cleanedMatch, 0, 2);
        $pref2 = substr($cleanedMatch, 2, 3);
        $pref3 = substr($cleanedMatch, 5, 3);
        $pref4 = substr($cleanedMatch, 8);

        return "00$pref1 $pref2 $pref3 $pref4";
    }
}
