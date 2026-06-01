<?php

namespace App\Services;

use Picqer\Barcode\BarcodeGeneratorPNG;

class BarcodeService
{
    public function make(?string $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        try {
            $generator = new BarcodeGeneratorPNG();

            return 'data:image/png;base64,' . base64_encode(
                $generator->getBarcode($value, BarcodeGeneratorPNG::TYPE_CODE_128, 2, 60)
            );
        } catch (\Throwable $e) {
            return null;
        }
    }
}
