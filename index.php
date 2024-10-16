<?php
include 'pdfparser-master/alt_autoload.php-dist';
require 'includes/autoload.php';
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
$pricesTransfer = new DBPricesTransfer();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        if (isset($_FILES['pdf']) && $_FILES['pdf']['error'] === UPLOAD_ERR_OK) {
            $destination = "uploads/" . basename($_FILES['pdf']['name']);
            $text = parsePDF($destination);
            $transferController = new TransferController($pricesTransfer, $text);
            pdfValidator($text);
            $transferController->processBookings();
        } else {
            throw new Exception('No PDF file uploaded or there was an upload error.');
        }
    } catch (Exception $er) {
        echo json_encode(['error' => $er->getMessage()]);
    }
}


function parsePDF($filePath)
{
    $parser = new \Smalot\PdfParser\Parser();
    // Parses the PDF content
    $pdf = $parser->parseContent(file_get_contents($filePath));
    // Extracts text from the PDF and replaces newlines with spaces
    $text = $pdf->getText();
    $text = str_replace("\n", " ", $text);
    return $text;
}


function pdfValidator($text)
{
    $bookingTypePattern = Patterns::$patternsBooking['bookingType'];
    if (!preg_match($bookingTypePattern, $text)) {
        throw new Exception('The format of PDF is not supported');
    }
}
