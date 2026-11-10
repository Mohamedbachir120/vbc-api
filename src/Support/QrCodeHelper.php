<?php

declare(strict_types=1);

namespace App\Support;

use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\RoundBlockSizeMode;

class QrCodeHelper
{
    public static function dataUriFor(string $url): string
    {
        $qrCode = new QrCode(
            data: $url,
            size: 300,
            margin: 10,
            errorCorrectionLevel: ErrorCorrectionLevel::High,
            roundBlockSizeMode: RoundBlockSizeMode::Margin,
        );

        $writer = new PngWriter();
        $result = $writer->write($qrCode);

        return $result->getDataUri();
    }
}
