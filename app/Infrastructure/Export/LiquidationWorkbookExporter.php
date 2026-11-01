<?php

namespace App\Infrastructure\Export;

use App\Domain\Enum\LiquidationVariant;
use App\Models\Liquidation;
use App\Models\LiquidationItem;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class LiquidationWorkbookExporter
{
    private const MONEY = '"$ "#,##0';

    private const NUMBER = '#,##0.####';

    private const TAXI = 'FFF4C430';

    private const PANEL = 'FFF2F3F5';

    /**
     * @var array<string, string>
     */
    private const EARNING_LABELS = [
        'CESANTIAS' => 'Cesantías',
        'CESANTIAS_INTERES' => 'Intereses sobre Cesantías',
        'PRIMA' => 'Prima de Servicios',
        'VACACIONES' => 'Vacaciones',
        'SALARIO_PENDIENTE' => 'Salario Pendiente (Días del mes)',
        'AUXILIO_TRANSPORTE' => 'Auxilio Transporte Pendiente',
        'HORAS_EXTRAS' => 'Horas Extras y Recargos del Período',
    ];

    /**
     * @var array<string, string>
     */
    private const DEDUCTION_LABELS = [
        'DESC_SALUD' => 'Aporte Salud (4% sobre Salario y Vacaciones)',
        'DESC_PENSION' => 'Aporte Pensión (4% sobre Salario y Vacaciones)',
        'DESC_PRESTAMOS' => 'Préstamos / Anticipos Pendientes',
        'DESC_OTRAS' => 'Otras Deducciones (Sindicato, Libranzas)',
    ];

    public function filename(Liquidation $liquidation): string
    {
        return $liquidation->folio.'.xlsx';
    }

    public function export(Liquidation $liquidation): string
    {
        $directory = storage_path('app/private/exports');

        if (! is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        $path = $directory.'/liquidacion-'.uniqid('', true).'.xlsx';

        (new Xlsx($this->book($liquidation)))->save($path);

        return $path;
    }

    public function book(Liquidation $liquidation): Spreadsheet
    {
        $book = new Spreadsheet;
        $sheet = $book->getActiveSheet();
        $sheet->setTitle('Liquidación');

        $contract = $liquidation->contract;
        $worker = $contract->worker;
        $snapshot = $liquidation->params_snapshot;
        $withIndemnization = $liquidation->variant === LiquidationVariant::ConIndemnizacion;
        $indemnization = $this->item($liquidation, 'INDEMNIZACION');

        $this->widths($sheet);

        $row = $this->heading($sheet, $withIndemnization);
        $row = $this->section($sheet, $row, '1. DATOS DEL TRABAJADOR Y DEL CONTRATO');
        $row = $this->pair($sheet, $row, 'Nombre del Empleado:', $worker->fullName(), 'Fecha de Ingreso:', $contract->start_date->format('d/m/Y'));
        $row = $this->pair($sheet, $row, 'Documento de Identidad:', $worker->document_number, 'Fecha de Retiro:', $liquidation->settlement_date->format('d/m/Y'));
        $row = $this->pair($sheet, $row, 'Cargo:', $contract->position->name, 'Tipo de Contrato:', $contract->contractType->name);
        $row = $this->pair($sheet, $row, 'Motivo de Retiro:', $this->text($contract->terminationReason?->description), 'Días Totales Laborados:', $liquidation->total_days_worked);
        $row = $this->pair($sheet, $row, 'Folio del documento:', $liquidation->folio, 'Días a Liquidar (Año):', $liquidation->cesantia_days);
        $row++;

        $row = $this->section($sheet, $row, '2. BASE SALARIAL Y PARÁMETROS DE CÁLCULO');
        $row = $this->pair($sheet, $row, 'Salario Básico Mensual:', (float) $contract->base_salary_monthly, 'Días Vacaciones Pendientes:', $liquidation->vacation_days, self::MONEY);
        $row = $this->pair($sheet, $row, 'Auxilio de Transporte Mensual:', (float) $contract->transport_auxiliary_monthly, 'Días Prima a Liquidar:', $liquidation->prima_days, self::MONEY);
        $row = $this->pair($sheet, $row, 'Promedio Horas Extras / Recargos:', (float) $contract->overtime_average_monthly, 'Días Cesantías a Liquidar:', $liquidation->cesantia_days, self::MONEY);
        $row = $this->pair($sheet, $row, 'Base Prestacional (Salario+Aux+Var):', (float) $liquidation->base_prestacional_snapshot, 'Base Vacaciones (Sin Aux. Transp):', (float) $liquidation->base_vacacional_snapshot, self::MONEY);
        $row = $this->pair($sheet, $row, 'SMLMV Vigente:', (float) $liquidation->smlmv_snapshot, 'Empresa:', $contract->company->business_name, self::MONEY);
        $row++;

        $row = $this->section($sheet, $row, $withIndemnization
            ? '3. LIQUIDACIÓN DE PRESTACIONES SOCIALES Y DÍAS PENDIENTES'
            : '3. LIQUIDACIÓN DE PRESTACIONES SOCIALES Y CONCEPTOS');
        $row = $this->tableHeader($sheet, $row, ['Concepto', 'Base de Cálculo', 'Días / Cantidad', 'Fórmula Aplicada', 'Valor a Pagar']);

        $earnedFirst = $row;

        foreach ($liquidation->items as $item) {
            if ($item->side->value !== 'earning' || $item->concept_code === 'INDEMNIZACION') {
                continue;
            }

            $row = $this->conceptRow($sheet, $row, $item, self::EARNING_LABELS);
        }

        $earnedLast = $row - 1;
        $indemnizationCell = null;

        if ($withIndemnization && $indemnization !== null) {
            $row++;
            [$row, $indemnizationCell] = $this->indemnizationBlock($sheet, $row, $indemnization, $snapshot, (float) $liquidation->smlmv_snapshot);
        }

        $earnedRow = $this->totalRow($sheet, $row, $withIndemnization ? 'TOTAL DEVENGADO + INDEMNIZACIÓN (A)' : 'TOTAL DEVENGADO (A)', (float) $liquidation->total_earned);
        $sheet->setCellValue('E'.$earnedRow, $this->earnedFormula($earnedFirst, $earnedLast, $indemnizationCell));
        $row = $earnedRow + 2;

        $row = $this->section($sheet, $row, $withIndemnization ? '5. DEDUCCIONES Y DESCUENTOS' : '4. DEDUCCIONES Y DESCUENTOS');
        $row = $this->tableHeader($sheet, $row, ['Concepto de Deducción', 'Base de Cálculo', 'Porcentaje / Valor', 'Descripción / Observación', 'Valor Deducción']);

        $deductedFirst = $row;

        foreach ($liquidation->items as $item) {
            if ($item->side->value !== 'deduction') {
                continue;
            }

            $row = $this->conceptRow($sheet, $row, $item, self::DEDUCTION_LABELS);
        }

        $deductedLast = $row - 1;
        $deductedRow = $this->totalRow($sheet, $row, 'TOTAL DEDUCCIONES (B)', (float) $liquidation->total_deductions);
        $sheet->setCellValue('E'.$deductedRow, sprintf('=SUM(E%d:E%d)', $deductedFirst, $deductedLast));
        $row = $deductedRow + 2;

        $netRow = $this->totalRow($sheet, $row, 'NETO TOTAL A RECIBIR (A - B)', (float) $liquidation->net_total, true);
        $sheet->setCellValue('E'.$netRow, sprintf('=E%d-E%d', $earnedRow, $deductedRow));
        $row = $netRow + 2;

        if ($liquidation->observations !== null) {
            $this->label($sheet, 'A'.$row, 'Observaciones:');
            $row = $this->paragraph($sheet, $row + 1, $liquidation->observations);
            $row++;
        }

        $notices = $snapshot['avisos'] ?? [];

        if (is_array($notices) && $notices !== []) {
            $this->label($sheet, 'A'.$row, 'Alertas del cálculo:');
            $row++;

            foreach ($notices as $notice) {
                $row = $this->paragraph($sheet, $row, '· '.(string) $notice);
            }

            $row++;
        }

        $this->signatures($sheet, $row, $worker->document_number, $contract->company->nit);

        return $book;
    }

    private function heading(Worksheet $sheet, bool $withIndemnization): int
    {
        $this->banner($sheet, 'A1', $withIndemnization
            ? 'LIQUIDACIÓN DE CONTRATO DE TRABAJO E INDEMNIZACIÓN'
            : 'LIQUIDACIÓN DE CONTRATO DE TRABAJO', true);

        $this->banner($sheet, 'A2', $withIndemnization
            ? 'FORMATO DE CÁLCULO DE PRESTACIONES SOCIALES E INDEMNIZACIÓN POR DESPIDO SIN JUSTA CAUSA'
            : 'FORMATO ESTÁNDAR DE LIQUIDACIÓN DE PRESTACIONES SOCIALES', false);

        return 4;
    }

    private function banner(Worksheet $sheet, string $cell, string $text, bool $colored): void
    {
        $last = substr($cell, 1);
        $sheet->mergeCells('A'.$last.':E'.$last);
        $sheet->setCellValue($cell, $text);

        $style = $sheet->getStyle($cell);
        $style->getFont()->setBold(true)->setSize($colored ? 14 : 10);
        $style->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        if ($colored) {
            $style->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB(self::TAXI);
        }
    }

    private function section(Worksheet $sheet, int $row, string $title): int
    {
        $sheet->mergeCells('A'.$row.':E'.$row);
        $sheet->setCellValue('A'.$row, $title);

        $style = $sheet->getStyle('A'.$row);
        $style->getFont()->setBold(true);
        $style->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB(self::PANEL);

        return $row + 1;
    }

    private function pair(Worksheet $sheet, int $row, string $leftLabel, mixed $leftValue, string $rightLabel, mixed $rightValue, string $format = self::NUMBER): int
    {
        $this->label($sheet, 'A'.$row, $leftLabel);
        $this->value($sheet, 'B'.$row, $leftValue, $format);
        $this->label($sheet, 'D'.$row, $rightLabel);
        $this->value($sheet, 'E'.$row, $rightValue, $format);

        return $row + 1;
    }

    private function label(Worksheet $sheet, string $cell, string $text): void
    {
        $sheet->setCellValue($cell, $text);
        $sheet->getStyle($cell)->getFont()->setBold(true);
    }

    private function text(?string $value): string
    {
        return $value ?? '—';
    }

    private function value(Worksheet $sheet, string $cell, mixed $value, string $format): void
    {
        if (is_string($value)) {
            $sheet->setCellValue($cell, $value);
            $sheet->getStyle($cell)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

            return;
        }

        $sheet->setCellValue($cell, (float) $value);
        $sheet->getStyle($cell)->getNumberFormat()->setFormatCode($format);
    }

    /**
     * @param  list<string>  $titles
     */
    private function tableHeader(Worksheet $sheet, int $row, array $titles): int
    {
        foreach ($titles as $index => $title) {
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($index + 1).$row, $title);
        }

        $style = $sheet->getStyle('A'.$row.':E'.$row);
        $style->getFont()->setBold(true);
        $style->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB(self::TAXI);
        $style->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $style->getAlignment()->setWrapText(true);
        $style->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        return $row + 1;
    }

    /**
     * @param  array<string, string>  $labels
     */
    private function conceptRow(Worksheet $sheet, int $row, LiquidationItem $item, array $labels): int
    {
        $sheet->setCellValue('A'.$row, $labels[$item->concept_code] ?? $item->concept_name);
        $sheet->setCellValue('B'.$row, (float) $item->calculation_base);
        $sheet->setCellValue('C'.$row, (float) $item->quantity);
        $sheet->setCellValue('D'.$row, $item->formula_text);
        $sheet->setCellValue('E'.$row, (float) $item->amount);

        $sheet->getStyle('B'.$row)->getNumberFormat()->setFormatCode(self::MONEY);
        $sheet->getStyle('C'.$row)->getNumberFormat()->setFormatCode(self::NUMBER);
        $sheet->getStyle('E'.$row)->getNumberFormat()->setFormatCode(self::MONEY);
        $sheet->getStyle('A'.$row.':E'.$row)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        return $row + 1;
    }

    /**
     * @param  array<string, mixed>  $snapshot
     * @return array{0: int, 1: string}
     */
    private function indemnizationBlock(Worksheet $sheet, int $row, LiquidationItem $item, array $snapshot, float $smlmv): array
    {
        $row = $this->section($sheet, $row, '4. INDEMNIZACIÓN POR DESPIDO SIN JUSTA CAUSA (ART. 64 C.S.T.)');

        $antiquity = is_array($snapshot['antiguedad'] ?? null) ? $snapshot['antiguedad'] : [];
        $indemnization = is_array($snapshot['indemnizacion'] ?? null) ? $snapshot['indemnizacion'] : [];
        $detail = (string) ($indemnization['detalle'] ?? '');
        $buckets = is_array($indemnization['dias'] ?? null) ? $indemnization['dias'] : null;

        $row = $this->pair($sheet, $row, 'Sueldo Base para Indemnización:', (float) $item->calculation_base, 'Rango Salarial:', $this->wageRange($item, $smlmv), self::MONEY);
        $row = $this->pair($sheet, $row, 'Años Completos de Servicio:', (int) ($antiquity['anos_completos'] ?? 0), 'Días Restantes (Fracción Año):', (int) ($antiquity['fraccion'] ?? 0));

        if ($buckets !== null) {
            $row = $this->pair(
                $sheet,
                $row,
                'Días a Indemnizar (1er Año):',
                (float) $buckets['primer_ano'],
                'Días a Indemnizar (Años Sig. Proporcional):',
                (float) $buckets['anios_siguientes'] + (float) $buckets['fraccion'],
            );
        }

        $valueRow = $row;
        $row = $this->pair($sheet, $row, 'Total Días a Indemnizar:', (float) $item->quantity, 'VALOR INDEMNIZACIÓN:', (float) $item->amount);

        $style = $sheet->getStyle('E'.$valueRow);
        $style->getNumberFormat()->setFormatCode(self::MONEY);
        $style->getFont()->setBold(true);

        if ($detail === '') {
            return [$row, 'E'.$valueRow];
        }

        return [$this->paragraph($sheet, $row, 'Desglose: '.$detail), 'E'.$valueRow];
    }

    private function wageRange(LiquidationItem $item, float $smlmv): string
    {
        return (float) $item->calculation_base <= $smlmv * 10 ? 'Menor a 10 SMLMV' : 'Mayor a 10 SMLMV';
    }

    private function earnedFormula(int $first, int $last, ?string $indemnizationCell): string
    {
        $sum = sprintf('=SUM(E%d:E%d)', $first, $last);

        return $indemnizationCell === null ? $sum : sprintf('%s+%s', $sum, $indemnizationCell);
    }

    private function totalRow(Worksheet $sheet, int $row, string $title, float $value, bool $net = false): int
    {
        $sheet->mergeCells('A'.$row.':D'.$row);
        $sheet->setCellValue('A'.$row, $title);
        $sheet->setCellValue('E'.$row, $value);

        $style = $sheet->getStyle('A'.$row.':E'.$row);
        $style->getFont()->setBold(true);
        $style->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $style->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB($net ? self::TAXI : self::PANEL);
        $style->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('E'.$row)->getNumberFormat()->setFormatCode(self::MONEY);

        return $row;
    }

    private function paragraph(Worksheet $sheet, int $row, string $text): int
    {
        $sheet->mergeCells('A'.$row.':E'.$row);
        $sheet->setCellValue('A'.$row, $text);
        $sheet->getStyle('A'.$row)->getAlignment()->setWrapText(true);
        $sheet->getRowDimension($row)->setRowHeight(45);

        return $row + 1;
    }

    private function signatures(Worksheet $sheet, int $row, string $documentNumber, string $nit): void
    {
        $row = $this->section($sheet, $row, 'DECLARACIÓN Y FIRMAS DE CONFORMIDAD');
        $row = $this->paragraph($sheet, $row, 'Hago constar que he recibido a entera satisfacción el valor total de la presente liquidación de prestaciones sociales, salarios e indemnización (si aplica), quedando la empresa a paz y salvo por todo concepto derivado de la relación laboral.');
        $row += 3;

        $sheet->setCellValue('A'.$row, '_____________________________________');
        $sheet->setCellValue('D'.$row, '_____________________________________');
        $sheet->setCellValue('A'.($row + 1), 'FIRMA DEL TRABAJADOR');
        $sheet->setCellValue('D'.($row + 1), 'EMPLEADOR / REPRESENTANTE LEGAL');
        $sheet->setCellValue('A'.($row + 2), 'C.C. No. '.$documentNumber);
        $sheet->setCellValue('D'.($row + 2), 'NIT / C.C. '.$nit);
    }

    private function widths(Worksheet $sheet): void
    {
        foreach (['A' => 38, 'B' => 20, 'C' => 18, 'D' => 38, 'E' => 20] as $column => $width) {
            $sheet->getColumnDimension($column)->setWidth($width);
        }
    }

    private function item(Liquidation $liquidation, string $code): ?LiquidationItem
    {
        foreach ($liquidation->items as $item) {
            if ($item->concept_code === $code) {
                return $item;
            }
        }

        return null;
    }
}
