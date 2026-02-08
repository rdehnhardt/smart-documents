<?php

namespace App\Services;

use App\Models\Document;
use chillerlan\QRCode\Common\EccLevel;
use chillerlan\QRCode\Data\QRMatrix;
use chillerlan\QRCode\Output\QROutputInterface;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

class QrCodeService
{
    /**
     * Generate a QR code SVG for a document's public URL.
     */
    public function generateSvg(Document $document, int $size = 200): ?string
    {
        if (! $document->isPublic()) {
            return null;
        }

        $url = $document->getPublicUrl();

        if (! $url) {
            return null;
        }

        $options = new QROptions([
            'outputType' => QROutputInterface::MARKUP_SVG,
            'eccLevel' => EccLevel::M,
            'scale' => 5,
            'addQuietzone' => true,
            'quietzoneSize' => 2,
            'svgViewBoxSize' => $size,
            'drawLightModules' => true,
            'moduleValues' => [
                QRMatrix::M_FINDER_DARK => '#000000',
                QRMatrix::M_FINDER_DOT => '#000000',
                QRMatrix::M_FINDER => '#ffffff',
                QRMatrix::M_ALIGNMENT_DARK => '#000000',
                QRMatrix::M_ALIGNMENT => '#ffffff',
                QRMatrix::M_TIMING_DARK => '#000000',
                QRMatrix::M_TIMING => '#ffffff',
                QRMatrix::M_FORMAT_DARK => '#000000',
                QRMatrix::M_FORMAT => '#ffffff',
                QRMatrix::M_VERSION_DARK => '#000000',
                QRMatrix::M_VERSION => '#ffffff',
                QRMatrix::M_DATA_DARK => '#000000',
                QRMatrix::M_DATA => '#ffffff',
                QRMatrix::M_QUIETZONE => '#ffffff',
            ],
        ]);

        $qrCode = new QRCode($options);

        return $qrCode->render($url);
    }

    /**
     * Generate a QR code as a data URI.
     */
    public function generateDataUri(Document $document, int $size = 200): ?string
    {
        // generateSvg already returns a data URI from chillerlan/php-qrcode
        return $this->generateSvg($document, $size);
    }

    /**
     * Generate a QR code for any URL.
     */
    public function generateForUrl(string $url, int $size = 200): string
    {
        $options = new QROptions([
            'outputType' => QROutputInterface::MARKUP_SVG,
            'eccLevel' => EccLevel::M,
            'scale' => 5,
            'addQuietzone' => true,
            'quietzoneSize' => 2,
            'svgViewBoxSize' => $size,
        ]);

        $qrCode = new QRCode($options);

        return $qrCode->render($url);
    }
}
