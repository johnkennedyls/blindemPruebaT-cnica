<?php

namespace App\Domain\Enum;

enum LiquidationFileKind: string
{
    case Excel = 'excel';
    case Pdf = 'pdf';
    case PdfFirmado = 'pdf_firmado';

    public function label(): string
    {
        return match ($this) {
            self::Excel => 'Excel',
            self::Pdf => 'PDF',
            self::PdfFirmado => 'PDF con firmas',
        };
    }

    public function mimeType(): string
    {
        return $this === self::Excel
            ? 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
            : 'application/pdf';
    }
}
