<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Citizen;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class PopulationExportController extends Controller
{
    private function tenantId(Request $request): string
    {
        $user = $request->user();
        $id = $user->isSuperAdmin() ? $request->query('tenant_id') : $user->tenant_id;
        abort_unless($id && Tenant::whereKey($id)->exists(), 422, 'Tenant belum dipilih.');
        return (string) $id;
    }

    public function citizens(Request $request)
    {
        abort_unless($request->user()->hasPermission('population.view'), 403);
        $tenantId = $this->tenantId($request);
        $rows = Citizen::where('tenant_id', $tenantId)->orderBy('nama_lengkap')->get();
        $headers = ['NIK','Nama Lengkap','Tempat Lahir','Tanggal Lahir','Jenis Kelamin','Golongan Darah','Agama','Status Perkawinan','Pendidikan','Pekerjaan','Kewarganegaraan','No Passport','No KITAP','Nama Ayah','NIK Ayah','Nama Ibu','NIK Ibu','Status Kependudukan'];
        $filename = 'data-warga-' . now()->format('Ymd-His');

        if ($request->query('format', 'xlsx') === 'csv') {
            return Response::streamDownload(function () use ($rows, $headers): void {
                $out = fopen('php://output', 'w');
                fwrite($out, "\xEF\xBB\xBF");
                fputcsv($out, $headers, ';');
                foreach ($rows as $c) fputcsv($out, [$c->nik,$c->nama_lengkap,$c->tempat_lahir,$c->tanggal_lahir?->format('Y-m-d'),$c->jenis_kelamin,$c->golongan_darah,$c->agama,$c->status_perkawinan,$c->pendidikan,$c->pekerjaan,$c->kewarganegaraan,$c->no_passport,$c->no_kitap,$c->nama_ayah,$c->nik_ayah,$c->nama_ibu,$c->nik_ibu,$c->status_kependudukan], ';');
                fclose($out);
            }, $filename . '.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Data Warga');
        $sheet->fromArray($headers, null, 'A1');
        foreach ($rows as $i => $c) $sheet->fromArray([$c->nik,$c->nama_lengkap,$c->tempat_lahir,$c->tanggal_lahir?->format('Y-m-d'),$c->jenis_kelamin,$c->golongan_darah,$c->agama,$c->status_perkawinan,$c->pendidikan,$c->pekerjaan,$c->kewarganegaraan,$c->no_passport,$c->no_kitap,$c->nama_ayah,$c->nik_ayah,$c->nama_ibu,$c->nik_ibu,$c->status_kependudukan], null, 'A' . ($i + 2));
        $sheet->freezePane('A2');
        foreach (range(1, count($headers)) as $col) $sheet->getColumnDimensionByColumn($col)->setAutoSize(true);
        $writer = new Xlsx($spreadsheet);
        return response()->streamDownload(function () use ($writer): void { $writer->save('php://output'); }, $filename . '.xlsx', ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    public function template(Request $request)
    {
        abort_unless($request->user()->hasPermission('population.manage'), 403);
        $headers = ['NIK','Nama Lengkap','Tempat Lahir','Tanggal Lahir','Jenis Kelamin','Golongan Darah','Agama','Status Perkawinan','Pendidikan','Pekerjaan','Kewarganegaraan','No Passport','No KITAP','Nama Ayah','NIK Ayah','Nama Ibu','NIK Ibu','Status Kependudukan'];
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet(); $sheet->setTitle('Template Warga'); $sheet->fromArray($headers, null, 'A1');
        $sheet->fromArray(['','Contoh Nama','Palangka Raya','2000-01-01','male','O','Islam','Belum Kawin','SMA','Pelajar','WNI','','','Nama Ayah','','Nama Ibu','','active'], null, 'A2');
        $sheet->getStyle('A1:R1')->getFont()->setBold(true); $sheet->freezePane('A2');
        foreach (range(1, count($headers)) as $col) $sheet->getColumnDimensionByColumn($col)->setAutoSize(true);
        $writer = new Xlsx($spreadsheet);
        return response()->streamDownload(function () use ($writer): void { $writer->save('php://output'); }, 'template-data-warga.xlsx', ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }
}
