<?php
require_once 'vendor/autoload.php';
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\SvgWriter;
use Endroid\QrCode\Encoding\Encoding;

/**
 * Génère une image QR code encodée en base64 (SVG - ne nécessite pas GD)
 * @param string $text Texte ou URL à encoder dans le QR code
 * @return string Image QR code SVG encodée en base64
 * @throws Exception
 */
function generateQrBase64(string $text): string {
    try {
        // Construction du QR code avec SVG Writer (pas besoin de GD)
        $builder = new Builder(
            writer: new SvgWriter(),
            data: $text,
            encoding: new Encoding('UTF-8'),
            size: 400,
            margin: 10
        );

        $result = $builder->build();

        // Conversion en base64 pour SVG
        return 'data:image/svg+xml;base64,' . base64_encode($result->getString());

    } catch (Exception $e) {
        throw new Exception("Erreur lors de la génération du QR code : " . $e->getMessage());
    }
}