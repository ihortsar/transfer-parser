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


    private $pricesTransfer;
    public function __construct(PricesTransferInterface $pricesTransfer)
    {
        $this->pricesTransfer = $pricesTransfer;
    }

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
        $this->patternsBookingMapping($patternsBooking, $text);
        $this->patternsHeaderMapping($patternsHeader, $headerText);
        $this->serviceClass = ServiceClass::getServiceCode($this->vehicleType);
        $this->pricesTransfer->assignTransfersPrices($this);
        return $this;
    }

    /**
     * Maps the booking information from the text using predefined patterns.
     * 
     * @param array $patternsBooking Array of booking patterns to match against.
     * @param string $text The booking details text.
     * @param Transfer $transfer The Transfer object to store the extracted values.
     * @return void
     */
    private function patternsBookingMapping($patternsBooking, $text)
    {
        foreach ($patternsBooking as $key => $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                $this->$key = Patterns::removeUnnesecerySpaces(($matches[1]));
                $text = ($key === 'flightDestination') ?
                    preg_replace('/To:\s*' . preg_quote($matches[1]) . '/', '', $text) : $text;
            } else {
                $this->$key = '';
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
    private function patternsHeaderMapping($patternsHeader, $headerText)
    {
        foreach ($patternsHeader as $key => $pattern) {
            if (preg_match($pattern, $headerText, $matches)) {
                $key === 'phoneArranger' ? $this->$key = Patterns::editPhoneNumber($matches[1] . $matches[2]) :
                    $this->$key = Patterns::removeUnnesecerySpaces(($matches[1]));
            } else {
                $this->$key = '';
            }
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
}
