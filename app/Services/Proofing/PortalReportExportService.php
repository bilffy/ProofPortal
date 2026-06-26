<?php

namespace App\Services\Proofing;

use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xls;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class PortalReportExportService
{
    public function export(Collection $rows, string $format, string $title): array
    {
        if ($rows->isEmpty()) {
            return ['body' => null, 'contentType' => null, 'extension' => null];
        }

        return match (strtolower($format)) {
            'csv' => $this->exportCsv($rows),
            'pdf' => $this->exportPdf($rows, $title),
            'xlsx', 'excelopenxml' => $this->exportXlsx($rows),
            'xls', 'excel' => $this->exportXls($rows),
            default => throw new \InvalidArgumentException("Unsupported export format: {$format}"),
        };
    }

    public function exportCsv(Collection $rows): array
    {
        return [
            'body' => SqlServerReportingServices::queryToCsvReport($rows),
            'contentType' => 'text/csv',
            'extension' => 'csv',
        ];
    }

    public function exportXlsx(Collection $rows): array
    {
        $spreadsheet = $this->buildSpreadsheet($rows);
        $writer = new Xlsx($spreadsheet);

        ob_start();
        $writer->save('php://output');
        $body = ob_get_clean();

        return [
            'body' => $body,
            'contentType' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'extension' => 'xlsx',
        ];
    }

    public function exportXls(Collection $rows): array
    {
        $spreadsheet = $this->buildSpreadsheet($rows);
        $writer = new Xls($spreadsheet);

        ob_start();
        $writer->save('php://output');
        $body = ob_get_clean();

        return [
            'body' => $body,
            'contentType' => 'application/vnd.ms-excel',
            'extension' => 'xls',
        ];
    }

    public function exportPdf(Collection $rows, string $title): array
    {
        $headers = $this->humanizeHeaders($rows->first());
        $html = view('proofing.reports.export-pdf', [
            'title' => $title,
            'headers' => $headers,
            'rows' => $rows->map(fn ($row) => array_values($this->formatRowValues($this->rowToArray($row))))->values(),
        ])->render();

        $options = new Options();
        $options->set('isRemoteEnabled', false);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        return [
            'body' => $dompdf->output(),
            'contentType' => 'application/pdf',
            'extension' => 'pdf',
        ];
    }

    private function buildSpreadsheet(Collection $rows): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $firstRow = $this->rowToArray($rows->first());
        $headers = $this->humanizeHeaders($firstRow);

        $sheet->fromArray($headers, null, 'A1');

        $data = $rows
            ->map(fn ($row) => array_values($this->formatRowValues($this->rowToArray($row))))
            ->values()
            ->all();

        $sheet->fromArray($data, null, 'A2');

        return $spreadsheet;
    }

    private function rowToArray(mixed $row): array
    {
        if (is_array($row)) {
            return $row;
        }

        if (is_object($row)) {
            return json_decode(json_encode($row), true) ?? [];
        }

        return [];
    }

    private function humanizeHeaders(mixed $row): array
    {
        return array_map(
            fn (string $header) => Str::title(str_replace('_', ' ', $header)),
            array_keys($this->rowToArray($row))
        );
    }

    private function formatRowValues(array $row): array
    {
        return array_map(function ($value) {
            if (is_null($value)) {
                return '';
            }

            if (is_bool($value)) {
                return $value ? 'true' : 'false';
            }

            if (is_scalar($value)) {
                return (string) $value;
            }

            return json_encode($value);
        }, $row);
    }
}
