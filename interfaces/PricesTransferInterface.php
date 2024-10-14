<?php

interface PricesTransferInterface
{
    public function getPriceDetails($city, $vehicleType, $airport);
    public function assignTransfersPrices($transfer);
}
