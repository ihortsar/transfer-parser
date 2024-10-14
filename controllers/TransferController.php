<?php

/**
 * Class TransferController handles the logic for processing multiple transfer bookings, including splitting the bookings,
 * handling round trips, modifications and one-way transfers. It uses the Transfer class to map and extract
 * values from booking text and stores the processed bookings in an array.
 */
class TransferController
{
    private $pricesTransfer;
    private $allBookings = [];

    public function __construct(PricesTransferInterface $pricesTransfer)
    {
        $this->pricesTransfer = $pricesTransfer;
    }

    /**
     * Splits the booking text into individual bookings and processes them.
     * 
     * @param string $text The full booking text from a PDF.
     * @return void
     */
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

    /**
     * Processes a single booking based on its content and checks if it is a one-way, round trip, or modification.
     * 
     * @param string $booking The booking text.
     * @param string $header The header text.
     * @return void
     */
    public function processBooking($booking, $header)
    {
        $modification = strpos($booking, 'Modification');
        $oldBookingPosition = strpos($booking, 'Old booking:');
        $roundTripSeparator = $this->findRoundTripSeparator($booking);

        if ($modification !== false) {
            $this->handleModifiedBooking($booking, $oldBookingPosition, $header);
        } elseif ($roundTripSeparator !== false) {
            $this->handleRoundTrip($booking, $roundTripSeparator, $header);
        } else {
            $transfer = new Transfer($this->pricesTransfer);
            $oneWayBooking = $transfer->extractValues($booking, $header);
            $this->allBookings[] = [
                'description' => 'oneWay',
                'details' => $oneWayBooking
            ];
        }
    }


    /**
     * Handles modified bookings by extracting new booking details and storing them.
     * 
     * @param string $text The booking text.
     * @param int $oldBookingPosition Position where the old booking details begin in the text.
     * @param string $header The header text.
     * @return void
     */
    private function handleModifiedBooking($text, $oldBookingPosition, $header)
    {
        $transfer = new Transfer($this->pricesTransfer);
        $newBooking = substr($text, 0, $oldBookingPosition);
        $newBookingFinal = $transfer->extractValues($newBooking, $header);
        $this->allBookings[] = [
            'description' => 'modification',
            'details' => $newBookingFinal
        ];
    }

    /**
     * Handles round-trip bookings by extracting both one-way and return.
     * 
     * @param string $text The full booking text.
     * @param int $roundTripSeparator Position separating the one-way and return.
     * @param string $header The header text.
     * @return void
     */
    private function handleRoundTrip($text, $roundTripSeparator, $header)
    {
        $transfer1 = new Transfer($this->pricesTransfer);
        $transfer2 = new Transfer($this->pricesTransfer);
        $oneWay = substr($text, 0, $roundTripSeparator);
        $return = substr($text, $roundTripSeparator);
        $firstBooking = $transfer1->extractValues($oneWay, $header);
        $secondBooking = $transfer2->extractValues($return, $header);
        $transfer1->assignTransferTypeIfRoundTrip($transfer1,  $transfer2);
        $this->allBookings[] = [
            'description' => 'roundTrip',
            'details' => [$firstBooking, $secondBooking]
        ];
    }

    /**
     * Extracts the header section from the booking text, which is placed before the first 'REFERENCE'.
     * 
     * @param string $text The full booking text.
     * @return string The extracted header section.
     */
    private function splitHeaderFromPDF($text)
    {
        $firstReferencePos = strpos($text, 'REFERENCE');
        $header = substr($text, 0, $firstReferencePos);
        return $header;
    }

    /**
     * Finds the separator in the booking text that divides the one-way and return in round-trip bookings.
     * 
     * @param string $booking The full booking text.
     * @return int|false Position of the separator or false if not found.
     */
    public static function findRoundTripSeparator($booking)
    {
        $roundTripSeparator = false;
        $departureCount = substr_count($booking, 'Departure:');
        $arrivalCount = substr_count($booking, 'Arrival:');
        if ($departureCount === $arrivalCount) {
            $departurePos = strpos($booking, 'Departure:');
            $arrivalPos = strpos($booking, 'Arrival:');
            $departurePos > $arrivalPos ? $roundTripSeparator =  $departurePos : $roundTripSeparator = $arrivalPos;
        }
        return $roundTripSeparator;
    }
}
