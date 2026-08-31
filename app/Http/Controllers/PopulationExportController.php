<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Citizen;
use App\Models\Tenant;
use App\Services\LibreOfficeSpreadsheetService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use RuntimeException;

class PopulationExportController extends Controller
{
    private const HEADERS = [
        'NIK', 'Nama Lengkap', 'Tempat Lahir', 'Tanggal Lahir', 'Jenis Kelamin',
        'Golongan Darah', 'Agama', 'Status Perkawinan', 'Pendidikan', 'Pekerjaan',
        'Kewarganegaraan', 'No Passport', 'No KITAP', 'Nama Ayah', 'NIK Ayah',
        'Nama Ibu', 'NIK Ibu', 'Status Kependudukan',
    ];

    private function tenantId(Request $request): string
    {
        $user = $request->user();
        $id = $user->isSuperAdmin() ? $request->query('tenant_id') : $user->tenant_id;
        abort_unless($id && Tenant::whereKey($id)->exists(), 422, 'Tenant belum dipilih.');

        return (string) $id;
    }

    public function citizens(Request $request, LibreOfficeSpreadsheetService $spreadsheet)
    {
        abort_unless($request->user()->hasPermission('population.view'), 403);
        $tenantId = $this->tenantId($request);
        $rows = Citizen::where('tenant_id', $tenantId)->orderBy('nama_lengkap')->get();
        $filename = 'data-warga-' . now()->format('Ymd-His');
        $format = strtolower((string) $request->query('format', 'xlsx'));

        if ($format === 'csv') {
            return $this->downloadCsv($rows, self::HEADERS, $filename . '.csv');
        }

        abort_unless($format === 'xlsx', 422, 'Format export tidak didukung.');

        $tmp = $this->writeCsv($rows, self::HEADERS, $filename);
        $outputDir = storage_path('app/temp/population-export');
        $xlsx = null;

        try {
            $xlsx = $spreadsheet->csvToXlsx($tmp, $outputDir, $filename . '.xlsx');

            return response()->download($xlsx, $filename . '.xlsx', [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])->deleteFileAfterSend(true);
        } finally {
            @unlink($tmp);
            if ($xlsx === null) {
                @unlink($outputDir . DIRECTORY_SEPARATOR . $filename . '.xlsx');
            }
        }
    }

    public function template(Request $request, LibreOfficeSpreadsheetService $spreadsheet)
    {
        abort_unless($request->user()->hasPermission('population.manage'), 403);

        $filename = 'template-data-warga-' . now()->format('Ymd-His');
        $tmp = $this->writeCsv(null, self::HEADERS, $filename, [
            ['', 'Contoh Nama', 'Palangka Raya', '2000-01-01', 'male', 'O', 'Islam', 'Belum Kawin', 'SMA', 'Pelajar', 'WNI', '', '', 'Nama Ayah', '', 'Nama Ibu', '', 'active'],
        ]);
        $outputDir = storage_path('app/temp/population-export');
        $xlsx = null;

        try {
            $xlsx = $spreadsheet->csvToXlsx($tmp, $outputDir, $filename . '.xlsx');

            return response()->download($xlsx, $filename . '.xlsx', [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])->deleteFileAfterSend(true);
        } finally {
            @unlink($tmp);
            if ($xlsx === null) {
                @unlink($outputDir . DIRECTORY_SEPARATOR . $filename . '.xlsx');
            }
        }
    }

    private function downloadCsv($rows, array $headers, string $filename)
    {
        return Response::streamDownload(function () use ($rows, $headers): void {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, $headers, ';');
            foreach ($rows as $c) {
                fputcsv($out, $this->citizenRow($c), ';');
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function writeCsv($rows, array $headers, string $filename, ?array $extraRows = null): string
    {
        $dir = storage_path('app/temp/population-export');
        if (! is_dir($dir) && ! mkdir($dir, 0777, true) && ! is_dir($dir)) {
            throw new RuntimeException('Direktori sementara export tidak dapat dibuat.');
        }

        $path = $dir . DIRECTORY_SEPARATOR . $filename . '.csv';
        $out = fopen($path, 'wb');
        if ($out === false) {
            throw new RuntimeException('File sementara export tidak dapat dibuat.');
        }

        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, $headers, ';');
        foreach ($rows ?? [] as $c) {
            fputcsv($out, $this->citizenRow($c), ';');
        }
        foreach ($extraRows ?? [] as $row) {
            fputcsv($out, $row, ';');
        }
        fclose($out);

        return $path;
    }

    private function citizenRow(Citizen $c): array
    {
        return [
            $c->nik, $c->nama_lengkap, $c->tempat_lahir, $c->tanggal_lahir?->format('Y-m-d'),
            $c->jenis_kelamin, $c->golongan_darah, $c->agama, $c->status_perkawinan,
            $c->pendidikan, $c->pekerjaan, $c->kewarganegaraan, $c->no_passport,
            $c->no_kitap, $c->nama_ayah, $c->nik_ayah, $c->nama_ibu, $c->nik_ibu,
            $c->status_kependudukan,
        ];
    }
}
