<?php
include 'pdfparser-master/alt_autoload.php-dist';
include 'classes/TransferInfo.php';
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $destination = "uploads/" . $_FILES['pdf']['name'];
    move_uploaded_file($_FILES['pdf']['tmp_name'], $destination);
    $text = parsePDF($destination);
    checkForDeparture($text);
}


function parsePDF($file_path)
{
    $parser = new \Smalot\PdfParser\Parser();
    $pdf = $parser->parseContent(file_get_contents($file_path));
    $text = $pdf->getText();
    $text = str_replace("\n", " ", $text);
    return $text;
}


function checkForDeparture($text)

{
    $modification = strpos($text, 'Modification');
    $oldBookingPosition = strpos($text, 'Old booking:');
    $twoBookingsSeparator = strpos($text, 'Departure:');
    if ($modification === false) {
        if ($twoBookingsSeparator !== false) {
            $oneWay = substr($text, 0, $twoBookingsSeparator);
            $return = substr($text, $twoBookingsSeparator);
            $firstBooking = extractValues($oneWay, 'first booking');
            $secondBooking = extractValues($return, 'second booking');
            echo json_encode([
                'details1' => $firstBooking,
                'details2' => $secondBooking,
                'description' => 'its a 2 ways booking'
            ]);
        } else {
            $oneWayBooking = extractValues($text, 'one way');
            echo json_encode(['description' => 'its a one way booking', 'details' => $oneWayBooking]);
        }
    } else {
        $newBooking = substr($text, 0,  $oldBookingPosition);
        $newBookingFinal = extractValues($newBooking, 'new booking');
        echo json_encode([
            'modification' => true,
            'description' => 'its a modificated booking',
            'details' => $newBookingFinal
        ]);
    }
}

function extractValues($text, $type)
{
    $patterns = [
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
    $transfer = new stdClass();
    $transfer->properties = new stdClass();
    $transfer->properties->language = 'en';

    foreach ($patterns as $key => $pattern) {
        if (preg_match($pattern, $text, $matches)) {
            $cleanedMatch = preg_replace('/\s+/', ' ', $matches[1]);
            if ($key === 'flightDestination') {
                $text = preg_replace('/To:\s*' . preg_quote($matches[1]) . '/', '', $text);
            }
            if ($key === 'phoneArranger') {
                $transfer->properties->$key = editPhoneNumber($matches);
            } else {
                $transfer->properties->$key = trim($cleanedMatch);
            }
        } else {
            $transfer->properties->$key = '';
        }
    }

    $transfer->type = $type;
    return $transfer;
}


function editPhoneNumber($matches)
{
    $match = $matches[1] . $matches[2];
    $cleanedMatch = preg_replace('/\s+/', ' ', $match);
    $formattedNumber = '00' . substr($cleanedMatch, 0, 2) . ' '
        . substr($cleanedMatch, 2, 3) . ' '
        . substr($cleanedMatch, 5, 3) . ' '
        . substr($cleanedMatch, 8);
    return $formattedNumber;
}
