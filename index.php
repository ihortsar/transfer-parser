<?php
include 'pdfparser-master/alt_autoload.php-dist';
require 'includes/autoload.php';
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

$pricesTransfer = new DBPricesTransfer();
$transferController = new TransferController($pricesTransfer);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $destination = "uploads/" . $_FILES['pdf']['name'];
    move_uploaded_file($_FILES['pdf']['tmp_name'], $destination);
    $text = parsePDF($destination);
    $transferController->splitBookings($text);
}


function parsePDF($filePath)
{
    $parser = new \Smalot\PdfParser\Parser();
    // Parses the PDF content and extract text
    $pdf = $parser->parseContent(file_get_contents($filePath));
    // Extracts text from the PDF and replace newlines with spaces
    $text = $pdf->getText();
    $text = str_replace("\n", " ", $text);
    return $text;
}
