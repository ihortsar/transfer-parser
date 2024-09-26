<?php
include 'Patterns.php';
class TransferManager
{

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
        echo json_encode([
            'description' => 'oneWay',
            'details' => $oneWayBooking
        ]);
    }


    private function handleModifiedBooking($text, $oldBookingPosition)
    {
        $newBooking = substr($text, 0, $oldBookingPosition);
        $newBookingFinal = $this->extractValues($newBooking, 'new booking');

        echo json_encode([
            'description' => 'modification',
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
            'details' => [$firstBooking, $secondBooking],
            'description' => 'roundTrip'
        ]);
    }


    private function extractValues($text, $type)
    {
        $patterns =  Patterns::$patterns;
        $transfer = new TransferInfo();
        foreach ($patterns as $key => $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                $key === 'phoneArranger' ? $transfer->$key = Patterns::editPhoneNumber($matches[1] . $matches[2]) : $transfer->$key = Patterns::removeUnnesecerySpaces(($matches[1]));
                $text = ($key === 'flightDestination') ? preg_replace('/To:\s*' . preg_quote($matches[1]) . '/', '', $text) : $text;
            } else {
                $transfer->$key = '';
            }
        }
        $transfer->serviceClass = ServiceClass::getServiceCode($transfer->vehicleType);
        $transfer->transferType = $type;
        return $transfer;
    }
}
