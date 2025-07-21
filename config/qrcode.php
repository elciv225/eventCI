<?php
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;

function generateQrBase64(string $text): string {
    $result = Builder::create()
        ->writer(new PngWriter())
        ->data($text)
        ->size(300)
        ->margin(10)
        ->build();

    // Convertir l’image PNG en base64
    $dataUri = $result->getDataUri();

    return $dataUri; // Exemple : data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAA...
}
