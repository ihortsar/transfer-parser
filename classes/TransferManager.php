<?php
require 'Database.php';
include 'Patterns.php';
class TransferManager
{
    public $db;
    public $conn;
    private $allBookings = [];

    public function __construct()
    {
        $this->db = new Database();
        $this->conn = $this->db->getConnection();
    }


    public function splitBookings($text)
    {
        $referenceCount = substr_count($text, 'REFERENCE');
        $header = $this->splitHeaderFromPDF($text);
        if ($referenceCount > 1) {
            $firstReferencePos = strpos($text, 'REFERENCE');
            $secondReferencePos = strpos($text, 'REFERENCE', $firstReferencePos + strlen('REFERENCE'));
            $firstBooking = substr($text, 0, $secondReferencePos);
            $this->processBooking($firstBooking, $header);
            $remainingText = substr($text, $secondReferencePos + strlen('REFERENCE'));
            $bookings = explode('REFERENCE', $remainingText);
            foreach ($bookings as $booking) {
                $this->processBooking('REFERENCE' . $booking, $header);
            }
        } else {
            $this->processBooking($text, $header);
        }
        echo json_encode($this->allBookings, JSON_PRETTY_PRINT);
    }


    private function splitHeaderFromPDF($text)
    {
        $firstReferencePos = strpos($text, 'REFERENCE');
        $header = substr($text, 0, $firstReferencePos);
        return $header;
    }


    private function processBooking($booking, $header)
    {
        $modification = strpos($booking, 'Modification');
        $oldBookingPosition = strpos($booking, 'Old booking:');
        $departureCount = substr_count($booking, 'Departure:');
        $arrivalCount = substr_count($booking, 'Arrival:');
        $twoBookingsSeparator = false;
        if ($departureCount === $arrivalCount) {
            $departurePos = strpos($booking, 'Departure:');
            $arrivalPos = strpos($booking, 'Arrival:');
            $departurePos > $arrivalPos ? $twoBookingsSeparator =  $departurePos : $twoBookingsSeparator = $arrivalPos;
        }

        if ($modification !== false) {
            $modificatedBooking = $this->handleModifiedBooking($booking, $oldBookingPosition, $header);
            $this->allBookings[] = [
                'description' => 'modification',
                'details' => $modificatedBooking
            ];
        } elseif ($twoBookingsSeparator !== false) {
            $twoBookingDetails = $this->handleRoundTrip($booking, $twoBookingsSeparator, $header);
            $this->allBookings[] = [
                'description' => 'roundTrip',
                'details' => $twoBookingDetails
            ];
        } else {
            $oneWayBooking = $this->extractValues($booking, 'one way', $header);
            $this->allBookings[] = [
                'description' => 'oneWay',
                'details' => $oneWayBooking
            ];
        }
    }


    private function handleModifiedBooking($text, $oldBookingPosition, $header)
    {
        $newBooking = substr($text, 0, $oldBookingPosition);
        $newBookingFinal = $this->extractValues($newBooking, 'new booking', $header);
        return $newBookingFinal;
    }


    private function handleRoundTrip($text, $twoBookingsSeparator, $header)
    {
        $oneWay = substr($text, 0, $twoBookingsSeparator);
        $return = substr($text, $twoBookingsSeparator);
        $firstBooking = $this->extractValues($oneWay, 'first booking', $header);
        $secondBooking = $this->extractValues($return, 'second booking', $header);
        $secondBooking->bookingType = $firstBooking->bookingType;
        return [
            $firstBooking,
            $secondBooking
        ];
    }


    private function extractValues($text, $type, $header)
    {
        $patternsBookings =  Patterns::$patternsBookings;
        $patternsHeader =  Patterns::$patternsHeader;
        $transfer = new TransferInfo();
        foreach ($patternsBookings as $key => $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                $transfer->$key = Patterns::removeUnnesecerySpaces(($matches[1]));
                $text = ($key === 'flightDestination') ? preg_replace('/To:\s*' . preg_quote($matches[1]) . '/', '', $text) : $text;
            } else {
                $transfer->$key = '';
            }
        }
        foreach ($patternsHeader as $key => $pattern) {
            if (preg_match($pattern, $header, $matches)) {
                $key === 'phoneArranger' ? $transfer->$key = Patterns::editPhoneNumber($matches[1] . $matches[2]) : $transfer->$key = Patterns::removeUnnesecerySpaces(($matches[1]));
            } else {
                $transfer->$key = '';
            }
        }
        $transfer->serviceClass = ServiceClass::getServiceCode($transfer->vehicleType);
        $transfer->transferType = $type;
        $this->assignTransfersPrices($transfer);
        return $transfer;
    }


    private function assignTransfersPrices($transfer)
    {
        $prices = $this->getPriceDetails($this->getCity($transfer), trim($transfer->vehicleType), $this->getAirport($transfer));
        $transfer->fixPriceClient = isset($prices['price_tdl']) ? $prices['price_tdl'] : '';
        $transfer->fixPriceGTcontractor = isset($prices['price_gt']) ? $prices['price_gt'] : '';
        $transfer->GTU = isset($prices['tdl_id']) ? $prices['tdl_id'] : '';
    }


    private function getPriceDetails($city, $vehicleType, $airport)
    {
        $sql = 'SELECT * FROM ota_518580 WHERE city LIKE :city AND vehicle= :vehicle AND airport=:airport';
        $stmt = $this->conn->prepare($sql);
        $city = '%' . $city . '%';
        $stmt->bindParam(':city', $city);
        $stmt->bindParam(':vehicle', $vehicleType);
        $stmt->bindParam(':airport', $airport);
        if ($stmt->execute()) {
            $prices = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($prices) {
                return  $prices;
            } else {
                return null;
            }
        }
    }


    private function getCity($transfer)
    {
        if (!empty($transfer->flightArrivalTime)) {
            $words = explode(' ', trim($transfer->toTransfer));
        } else {
            $words = explode(' ', trim($transfer->fromTransfer));
        }
        $city = trim(array_pop($words));
        if (isset(Patterns::$cityMappings[strtolower($city)])) {
            return ucwords(Patterns::$citiesVariations[strtolower($city)]);
        }
        return ucwords($city);
    }


    private function getAirport($transfer)
    {
        if (!empty($transfer->flightArrivalTime)) {
            return $transfer->flightDestination;
        } else {
            return  $transfer->flightOrigin;
        }
    }
}
