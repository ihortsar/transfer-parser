<?php
class DBPricesTransfer implements PricesTransferInterface
{

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


    /**
     * Assigns transfer prices from the database based on the city, vehicle type, and airport.
     * 
     * @param Transfer $transfer The transfer object to assign prices to.
     * @return void
     */
    public function assignTransfersPrices($transfer)
    {
        $prices = $this->getPriceDetails($this->getCity($transfer), trim($transfer->vehicleType), $this->getAirport($transfer));
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
}
