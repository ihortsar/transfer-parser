<?php
include 'pdfparser-master/alt_autoload.php-dist';
include 'classes/TransferInfo.php';
include 'classes/TransferManager.php';
include 'classes/ServiceClass.php';


header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

$serviceClass = new ServiceClass();
$transferManager = new TransferManager($serviceClass);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $destination = "uploads/" . $_FILES['pdf']['name'];
    move_uploaded_file($_FILES['pdf']['tmp_name'], $destination);
    $text = parsePDF($destination);
    $transferManager->checkForDeparture($text);
}


function parsePDF($file_path)
{
    $parser = new \Smalot\PdfParser\Parser();
    $pdf = $parser->parseContent(file_get_contents($file_path));
    $text = $pdf->getText();
    $text = str_replace("\n", " ", $text);
    return $text;
}
