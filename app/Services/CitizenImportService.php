<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Citizen;
use App\Models\Tenant;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use RuntimeException;

class CitizenImportService
{
    public function tenantExists(string $tenantId): bool
    {
        return Tenant::query()->whereKey($tenantId)->exists();
    }

    public function tenants(): Collection
    {
        return Tenant::query()->orderBy('name')->get(['id', 'name', 'code']);
    }

    public function preview(UploadedFile $file, string $tenantId, string $duplicateMode, LibreOfficeSpreadsheetService $spreadsheet): array
    {
        $path = $file->getRealPath();
        $extension = strtolower($file->getClientOriginalExtension());
        $temporaryCsv = null;
        $errors = [];
        $rows = [];

        try {
            if (in_array($extension, ['xlsx', 'xls'], true)) {
                $directory = storage_path('app' . DIRECTORY_SEPARATOR . 'tmp' . DIRECTORY_SEPARATOR . 'population-import');
                $temporaryCsv = $spreadsheet->spreadsheetToCsv(
                    $path,
                    $directory,
                    'population-import-' . bin2hex(random_bytes(6)) . '.csv',
                );
                $path = $temporaryCsv;
            }

            $raw = $this->readRows($path);
            if (count($raw) < 2) {
                return ['rows' => [], 'errors' => ['File tidak memiliki baris data.'], 'validCount' => 0, 'invalidCount' => 0];
            }

            $headerMap = $this->headerMap(array_shift($raw));
            foreach (['nik', 'nama lengkap'] as $required) {
                if (! array_key_exists($required, $headerMap)) {
                    $errors[] = 'Kolom wajib tidak ditemukan: ' . strtoupper($required) . '.';
                }
            }
            if ($errors) {
                return ['rows' => [], 'errors' => $errors, 'validCount' => 0, 'invalidCount' => 0];
            }

            $rows = $this->normalizeRows($raw, $headerMap);
            $this->markFileDuplicates($rows);

            $niks = array_values(array_unique(array_filter(array_column($rows, 'nik'))));
            $existingNiks = $niks
                ? Citizen::query()->where('tenant_id', $tenantId)->whereIn('nik', $niks)->pluck('nik')->flip()
                : collect();

            foreach ($rows as $i => $item) {
                $validation = Validator::make($item, [
                    'nik' => ['required', 'digits:16'],
                    'nama_lengkap' => ['required', 'string', 'max:255'],
                    'tanggal_lahir' => ['nullable', 'date'],
                    'jenis_kelamin' => ['nullable', 'in:male,female'],
                    'golongan_darah' => ['nullable', 'in:A,B,AB,O,unknown'],
                    'nik_ayah' => ['nullable', 'digits:16'],
                    'nik_ibu' => ['nullable', 'digits:16'],
                ]);

                if ($validation->fails()) {
                    $rows[$i]['_error'] = implode(' ', $validation->errors()->all());
                }

                if ($existingNiks->has($item['nik']) && $duplicateMode === 'skip') {
                    $rows[$i]['_error'] = 'NIK sudah ada — akan dilewati.';
                }
            }

            $validCount = collect($rows)->whereNull('_error')->count();

            return [
                'rows' => $rows,
                'errors' => [],
                'validCount' => $validCount,
                'invalidCount' => count($rows) - $validCount,
            ];
        } finally {
            if ($temporaryCsv && is_file($temporaryCsv)) {
                @unlink($temporaryCsv);
            }
        }
    }

    public function import(array $rows, string $tenantId, string $duplicateMode, int|string $userId): int
    {
        if ($rows === []) {
            return 0;
        }

        return DB::transaction(function () use ($rows, $tenantId, $duplicateMode, $userId): int {
            $niks = array_values(array_unique(array_filter(array_column($rows, 'nik'))));
            $existing = Citizen::query()
                ->where('tenant_id', $tenantId)
                ->whereIn('nik', $niks)
                ->get()
                ->keyBy('nik');

            $creates = [];
            $count = 0;
            $now = now();

            foreach ($rows as $row) {
                $citizen = $existing->get($row['nik']);

                if ($citizen) {
                    if ($duplicateMode === 'update' && $row['_error'] === 'NIK sudah ada — akan dilewati.') {
                        $citizen->update($this->cleanRow($row, $tenantId) + ['updated_by' => $userId]);
                        $count++;
                    }
                    continue;
                }

                if ($row['_error']) {
                    continue;
                }

                $data = $this->cleanRow($row, $tenantId);
                $data['id'] = (string) Str::uuid();
                $data['created_by'] = $userId;
                $data['updated_by'] = $userId;
                $data['created_at'] = $now;
                $data['updated_at'] = $now;
                $creates[] = $data;
                $count++;
            }

            foreach (array_chunk($creates, 500) as $chunk) {
                if ($chunk) {
                    Citizen::insert($chunk);
                }
            }

            return $count;
        });
    }

    private function readRows(string $path): array
    {
        $handle = fopen($path, 'rb');
        if ($handle === false) {
            throw new RuntimeException('File import tidak dapat dibaca.');
        }

        try {
            $rows = [];
            while (($row = fgetcsv($handle, 0, ';')) !== false) {
                if (count($row) === 1 && str_contains((string) $row[0], ',')) {
                    $row = str_getcsv($row[0], ',');
                }
                $rows[] = $row;
            }

            return $rows;
        } finally {
            fclose($handle);
        }
    }

    private function headerMap(array $headers): array
    {
        $map = [];
        foreach ($headers as $index => $header) {
            $header = preg_replace('/^\xEF\xBB\xBF/', '', (string) $header);
            $map[strtolower(trim($header))] = $index;
        }

        return $map;
    }

    private function normalizeRows(array $raw, array $headerMap): array
    {
        $rows = [];
        foreach ($raw as $index => $rawRow) {
            $item = ['line' => $index + 2];
            foreach ($this->headers() as $source => $target) {
                $item[$target] = isset($headerMap[$source])
                    ? trim((string) ($rawRow[$headerMap[$source]] ?? ''))
                    : '';
            }

            $item['kewarganegaraan'] = $item['kewarganegaraan'] ?: 'WNI';
            $item['status_kependudukan'] = $item['status_kependudukan'] ?: 'active';
            $item['_error'] = null;

            if ($item['nik'] === '' && $item['nama_lengkap'] === '') {
                continue;
            }

            $rows[] = $item;
        }

        return $rows;
    }

    private function markFileDuplicates(array &$rows): void
    {
        $seen = [];
        foreach ($rows as $index => $row) {
            if ($row['nik'] !== '') {
                $seen[$row['nik']][] = $index;
            }
        }

        foreach ($seen as $indexes) {
            if (count($indexes) > 1) {
                foreach ($indexes as $index) {
                    $rows[$index]['_error'] = 'NIK duplikat di dalam file.';
                }
            }
        }
    }

    private function cleanRow(array $row, string $tenantId): array
    {
        return collect($row)
            ->except(['line', '_error'])
            ->merge(['tenant_id' => $tenantId])
            ->toArray();
    }

    private function headers(): array
    {
        return [
            'nik' => 'nik',
            'nama lengkap' => 'nama_lengkap',
            'tempat lahir' => 'tempat_lahir',
            'tanggal lahir' => 'tanggal_lahir',
            'jenis kelamin' => 'jenis_kelamin',
            'golongan darah' => 'golongan_darah',
            'agama' => 'agama',
            'status perkawinan' => 'status_perkawinan',
            'pendidikan' => 'pendidikan',
            'pekerjaan' => 'pekerjaan',
            'kewarganegaraan' => 'kewarganegaraan',
            'no passport' => 'no_passport',
            'no kitap' => 'no_kitap',
            'nama ayah' => 'nama_ayah',
            'nik ayah' => 'nik_ayah',
            'nama ibu' => 'nama_ibu',
            'nik ibu' => 'nik_ibu',
            'status kependudukan' => 'status_kependudukan',
        ];
    }
}
