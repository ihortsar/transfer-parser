<?php
class TransferManager
{

    private $patterns;
    private $serviceClass;

    public function __construct(Patterns $patterns, ServiceClass $serviceClass)
    {
        $this->patterns = $patterns;
        $this->serviceClass = $serviceClass;
    }


    public function checkForDeparture($text)
    {
        $modification = strpos($text, 'Modification');
        $oldBookingPosition = strpos($text, 'Old booking:');
        $twoBookingsSeparator = strpos($text, 'Departure:');
        if ($modification !== false) {
            $this->handleModifiedBooking($text, $oldBookingPosition);
            return;
        }
        if ($twoBookingsSeparator !== false) {
            $this->handleTwoBookings($text, $twoBookingsSeparator);
            return;
        }
        $oneWayBooking = $this->extractValues($text, 'one way');
        echo json_encode(['description' => 'its a one way booking', 'details' => $oneWayBooking]);
    }


    private function handleModifiedBooking($text, $oldBookingPosition)
    {
        $newBooking = substr($text, 0, $oldBookingPosition);
        $newBookingFinal = $this->extractValues($newBooking, 'new booking');

        echo json_encode([
            'modification' => true,
            'description' => 'its a modified booking',
            'details' => $newBookingFinal
        ]);
    }


    private function handleTwoBookings($text, $twoBookingsSeparator)
    {
        $oneWay = substr($text, 0, $twoBookingsSeparator);
        $return = substr($text, $twoBookingsSeparator);
        $secondBooking = $this->extractValues($return, 'second booking');
        $firstBooking = $this->extractValues($oneWay, 'first booking');

        echo json_encode([
            'details1' => $firstBooking,
            'details2' => $secondBooking,
            'description' => 'its a 2-way booking'
        ]);
    }


    private function extractValues($text, $type)
    {
        $patterns =  $this->patterns->getPatterns();
        $transfer = new TransferInfo();
        foreach ($patterns as $key => $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                $cleanedMatch =   $this->patterns->removeUnnesecerySpaces(($matches));
                if ($key === 'flightDestination') {
                    $text = preg_replace('/To:\s*' . preg_quote($matches[1]) . '/', '', $text);
                }
                $this->patterns->setProperty($transfer, $key, $cleanedMatch, $matches);
            } else {
                $transfer->$key = '';
            }
        }
        $transfer->serviceClass =  $this->serviceClass->getServiceCode($transfer->vehicleType);
        $transfer->transferType = $type;
        return $transfer;
    }
}
