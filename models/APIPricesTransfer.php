<?php
/**class for testing dependency inversion */
class APIPricesTransfer implements PricesTransferInterface
{


    public function assignTransfersPrices($transfer)
    {
        $transfer->fixPriceClient = '25eur';
        $transfer->fixPriceGTcontractor = '25eur';
        $transfer->GTU = '25eur';
    }


    public function getPriceDetails($city, $vehicleType, $airport)
    {
        return true;
    }
}
