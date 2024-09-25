<?php

class Patterns
{

    public static function getPatterns()
    {
        return [
            'contactPerson' => '/^\s*([a-z]+)/i',
            'mainContact' => '/Name:\s*([a-zÀ-Ÿ0-9\s,.\'-]+?)(?=TO\.Ref)/i',
            'email' => '/email\s*:\s*([a-zÀ-Ÿ0-9\@.-_]+?)(?=\s*supplier)/i',
            'phoneArranger' => '/Tel\s*:\s*\(\+([0-9]+)\)\s*([0-9]+)/i',
            'phone' => '/mobile\s*contact:\s*([0-9]+)/i',
            'bookingConf' => '/-+\s*(\w+)\s*-+/i ',
            'arrival' => '/Arrival:\s*([0-9a-z,-:]+)/i',
            'departure' => '/Departure:\s*([0-9a-z,-:]+)/i',
            'serviceID' => '/SERVICE\s*ID:\s*([0-9-]+)/',
            'serviceClass' => '/Commercial\s*description:\s*([a-z\s*]+?)(?=\s*Serv)/i',
            'paxTotal' => '/Paxes:\s*([0-9]+?)(?=\s*\()/i',
            'fromTransfer' => '/From\s*:\s*([a-zÀ-Ÿ0-9\s,:.]+?)(?=Pickup)/i',
            'pickupDate' => '/Up\s*Time\s*:\s*([0-9\/]+)/i',
            'pickupTime' => '/Up\s*Time\s*:\s*[0-9\/]+\s*([0-9:]+)/i',
            'flightNumber' => '/Transport:\s*([A-Z]{1,5}\s*[0-9]{1,9})/',
            'flightDepartTime' => '/Time\s*of\s*Depart.:\s*([0-9\:]+?)(?=\s*hrs)/i',
            'flightArrivalTime' => '/Arrival\s*Time\s*:\s*([0-9\:]+?)(?=\s*hrs)/i',
            'flightOrigin' => '/hrs\s*From:\s*([A-Z]{3})/',
            'flightDestination' => '/To:\s*([A-Z]{3})/',
            'caseInvoice' => '/SERVICE ID:\s*([0-9-]+)-/',
            'remarks' => '/AD\+([0-9]+)/i',
            'toTransfer' => '/To\s*:\s*([a-zA-Z0-9\s,:-]+?)(?=(?:\s*Tran|$|\s*Page))/i',
            'vehicleType' => '/Vehicle\s*:\s*([a-z\s]+? )(?=\s*Paxes)/i'
        ];
    }



    public static function getPattern($name)
    {
        $patterns = self::getPatterns();
        return $patterns[$name] ?? null;
    }



    public static function removeUnnesecerySpaces($matches)
    {
        $cleanedMatch = preg_replace('/\s+/', ' ', $matches[1]);
        return $cleanedMatch;
    }



    public static function editPhoneNumber($matches)
    {
        $match = $matches[1] . $matches[2];
        $cleanedMatch = preg_replace('/\s+/', ' ', $match);
        $formattedNumber = '00' . substr($cleanedMatch, 0, 2) . ' '
            . substr($cleanedMatch, 2, 3) . ' '
            . substr($cleanedMatch, 5, 3) . ' '
            . substr($cleanedMatch, 8);
        return $formattedNumber;
    }



    public static function setProperty($transfer, $key, $cleanedMatch, $matches)
    {
        if ($key === 'phoneArranger') {
            $transfer->$key = self::editPhoneNumber($matches);
        } else {
            $transfer->$key = trim($cleanedMatch);
        }
    }
}
