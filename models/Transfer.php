<?php

/**
 * This class represents a transfer booking and contains fields and methods to process
 * the booking details, map patterns from text to booking fields and retrieve pricing details.
 */
class Transfer
{
    public $mainContact = "";
    public $phone = "";
    public $email = "";
    public $bookingConf = "";
    public $paxTotal = "";
    public $pickupDate = "";
    public $pickupTime = "";
    public $fromTransfer = "";
    public $toTransfer = "";
    public $remarks = "";
    public $paymentMethod = "";
    public $fixPriceClient = "";
    public $fixPriceGTcontractor = "";
    public $routingPrice = "";
    public $contactPerson = "";
    public $emailBookingArranger = "";
    public $phoneBookingArranger = "";
    public $arrangerBookingConfirmation = "";
    public $language = 'en';
    public $GTU = "";
    public $AgencyNr = "";
    public $cc_id_GT = "";
    public $caseInvoice = "";
    public $externalBookingIdent = "";
    public $internalBookingIdent = "";
    public $transactionType = "";
    public $serviceClass = "";
    public $vehicleType = "";
    public $flightOrigin = "";
    public $flightDestination = "";
    public $flightArrivalTime = "";
    public $flightDepartTime = "";
    public $flightNumber = "";
    public $airportTransferFromTo = "";
    public $ModeExpedia = "";
    public $VIP = "";
    public $CustomerID = "";
    public $CityPriceID = "";
    public $bookingType = "";

    /**
     * Extracts the values from the given booking text and header text using predefined patterns.
     * Maps these values to the appropriate fields in the transfer instance and assigns prices.
     *
     * @param string $text The booking details text.
     * @param string $headerText The header text with additional booking information.
     * @return Transfer Returns the populated Transfer object.
     */
    public function extractValues($text, $headerText)
    {
        $patternsBooking =  Patterns::$patternsBooking;
        $patternsHeader =  Patterns::$patternsHeader;
        $transfer = new Transfer();
        $this->patternsBookingMapping($patternsBooking, $text, $transfer);
        $this->patternsHeaderMapping($patternsHeader, $headerText, $transfer);
        $transfer->serviceClass = ServiceClass::getServiceCode($transfer->vehicleType);
        $this->assignTransfersPrices($transfer);
        return $transfer;
    }

    /**
     * Maps the booking information from the text using predefined patterns.
     * 
     * @param array $patternsBooking Array of booking patterns to match against.
     * @param string $text The booking details text.
     * @param Transfer $transfer The Transfer object to store the extracted values.
     * @return void
     */
    private function patternsBookingMapping($patternsBooking, $text, $transfer)
    {
        foreach ($patternsBooking as $key => $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                $transfer->$key = Patterns::removeUnnesecerySpaces(($matches[1]));
                $text = ($key === 'flightDestination') ?
                    preg_replace('/To:\s*' . preg_quote($matches[1]) . '/', '', $text) : $text;
            } else {
                $transfer->$key = '';
            }
        }
    }

    /**
     * Maps the header information to the Transfer object fields based on predefined patterns.
     * With special handling for phone numbers.
     * 
     * @param array $patternsHeader Array of header patterns to match against.
     * @param string $headerText The header text with additional booking information.
     * @param Transfer $transfer The Transfer object to store the extracted values.
     * @return void
     */
    private function patternsHeaderMapping($patternsHeader, $headerText, $transfer)
    {
        foreach ($patternsHeader as $key => $pattern) {
            if (preg_match($pattern, $headerText, $matches)) {
                $key === 'phoneArranger' ? $transfer->$key = Patterns::editPhoneNumber($matches[1] . $matches[2]) :
                    $transfer->$key = Patterns::removeUnnesecerySpaces(($matches[1]));
            } else {
                $transfer->$key = '';
            }
        }
    }

    /**
     * Assigns transfer prices from the database based on the city, vehicle type, and airport.
     * 
     * @param Transfer $transfer The transfer object to assign prices to.
     * @return void
     */
    private function assignTransfersPrices($transfer)
    {
        $prices = $transfer->getPriceDetails($this->getCity($transfer), trim($transfer->vehicleType), $this->getAirport($transfer));
        $transfer->fixPriceClient = isset($prices['price_tdl']) ? $prices['price_tdl'] : '';
        $transfer->fixPriceGTcontractor = isset($prices['price_gt']) ? $prices['price_gt'] : '';
        $transfer->GTU = isset($prices['tdl_id']) ? $prices['tdl_id'] : '';
    }

    /**
     * Retrieves the city name from the toTransfer or fromTransfer fields.
     * 
     * @param Transfer $transfer The transfer object with the booking details.
     * @return string The extracted city name.
     */
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

    /**
     * Retrieves the appropriate airport (either flightDestination or flightOrigin based on flightArrivalTime presence).
     * 
     * @param Transfer $transfer The transfer object containing flight details.
     * @return string The airport code.
     */
    private function getAirport($transfer)
    {
        if (!empty($transfer->flightArrivalTime)) {
            return $transfer->flightDestination;
        } else {
            return  $transfer->flightOrigin;
        }
    }

    /**
     * Assigns the same booking type to both parts of a round trip.
     * 
     * @param Transfer $firstBooking The first leg of the round trip.
     * @param Transfer $secondBooking The second leg of the round trip.
     * @return void
     */
    public function assignTransferTypeIfRoundTrip($firstBooking, $secondBooking)
    {
        $secondBooking->bookingType = $firstBooking->bookingType;
    }

    /**
     * Retrieves price details from the database based on the city, vehicle type, and airport.
     * 
     * @param string $city The city for which the prices are retrieved.
     * @param string $vehicleType The vehicle type.
     * @param string $airport The airport code.
     * @return array|null Returns the prices as an associative array, or null if not found.
     */
    public function getPriceDetails($city, $vehicleType, $airport)
    {
        $conn = require './includes/db.php';
        $sql = 'SELECT * FROM ota_518580 WHERE city LIKE :city AND vehicle= :vehicle AND airport=:airport';
        $stmt = $conn->prepare($sql);
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
}
