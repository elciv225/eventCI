<?php
require_once 'vendor/autoload.php';
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;

/**
 * Génère une image QR code encodée en base64
 * @param string $text Texte ou URL à encoder dans le QR code
 * @return string Image QR code encodée en base64 avec le préfixe data:image/png;base64
 */
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
