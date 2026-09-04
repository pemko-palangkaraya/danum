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
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\IOFactory;
use RuntimeException;

class CitizenImportService
{
    private const DUPLICATE_MODES = ['skip', 'update'];

    public function tenantExists(string $tenantId): bool
    {
        return Str::isUuid($tenantId) && Tenant::query()->whereKey($tenantId)->exists();
    }

    public function tenants(): Collection
    {
        return Tenant::query()->orderBy('name')->get(['id', 'name', 'code']);
    }

    public function preview(UploadedFile $file, string $tenantId, string $duplicateMode): array
    {
        $this->validateTenant($tenantId);
        $this->validateDuplicateMode($duplicateMode);

        $path = $file->getRealPath();
        $extension = strtolower($file->getClientOriginalExtension());

        if (! is_string($path) || ! is_file($path)) {
            throw new RuntimeException('File import tidak ditemukan.');
        }

        $raw = in_array($extension, ['xlsx', 'xls'], true)
            ? $this->readSpreadsheetRows($path)
            : $this->readRows($path);

        if (count($raw) < 2) {
            return [
                'rows' => [],
                'errors' => ['File tidak memiliki baris data.'],
                'validCount' => 0,
                'invalidCount' => 0,
            ];
        }

        $headerMap = $this->headerMap(array_shift($raw));
        $missingHeaders = array_diff(['nik', 'nama lengkap'], array_keys($headerMap));

        if ($missingHeaders !== []) {
            return [
                'rows' => [],
                'errors' => array_map(
                    static fn (string $header): string => 'Kolom wajib tidak ditemukan: '.strtoupper($header).'.',
                    $missingHeaders,
                ),
                'validCount' => 0,
                'invalidCount' => 0,
            ];
        }

        $rows = $this->normalizeRows($raw, $headerMap);
        $this->markFileDuplicates($rows);

        $niks = array_values(array_unique(array_filter(array_column($rows, 'nik'))));
        $existingNiks = $niks === []
            ? collect()
            : Citizen::query()
                ->where('tenant_id', $tenantId)
                ->whereIn('nik', $niks)
                ->pluck('nik')
                ->flip();

        foreach ($rows as $i => $item) {
            $validation = Validator::make($item, $this->rowRules());

            if ($validation->fails()) {
                $rows[$i]['_error'] = implode(' ', $validation->errors()->all());
                continue;
            }

            if ($existingNiks->has($item['nik'])) {
                $rows[$i]['_duplicate'] = true;

                if ($duplicateMode === 'skip') {
                    $rows[$i]['_error'] = 'NIK sudah ada — akan dilewati.';
                }
            }
        }

        $validCount = collect($rows)->whereNull('_error')->count();

        return [
            'rows' => $rows,
            'errors' => [],
            'validCount' => $validCount,
            'invalidCount' => count($rows) - $validCount,
        ];
    }

    public function import(array $rows, string $tenantId, string $duplicateMode, int|string $userId): int
    {
        $this->validateTenant($tenantId);
        $this->validateDuplicateMode($duplicateMode);

        if ($rows === []) {
            return 0;
        }

        return DB::transaction(function () use ($rows, $tenantId, $duplicateMode, $userId): int {
            $niks = array_values(array_unique(array_filter(array_column($rows, 'nik'))));
            $existing = $niks === []
                ? collect()
                : Citizen::query()
                    ->where('tenant_id', $tenantId)
                    ->whereIn('nik', $niks)
                    ->get()
                    ->keyBy('nik');

            $creates = [];
            $count = 0;
            $now = now();

            foreach ($rows as $row) {
                $validation = Validator::make($row, $this->rowRules());

                if ($validation->fails()) {
                    continue;
                }

                $citizen = $existing->get($row['nik']);

                if ($citizen) {
                    if ($duplicateMode === 'update') {
                        $citizen->update($this->cleanRow($row, $tenantId) + ['updated_by' => $userId]);
                        $count++;
                    }

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
                Citizen::insert($chunk);
            }

            return $count;
        });
    }

    private function validateTenant(string $tenantId): void
    {
        if (! $this->tenantExists($tenantId)) {
            throw ValidationException::withMessages([
                'tenant_id' => 'Tenant tidak valid atau tidak ditemukan.',
            ]);
        }
    }

    private function validateDuplicateMode(string $duplicateMode): void
    {
        Validator::make(
            ['duplicate_mode' => $duplicateMode],
            ['duplicate_mode' => ['required', 'string', Rule::in(self::DUPLICATE_MODES)]],
        )->validate();
    }

    private function rowRules(): array
    {
        return [
            'nik' => ['required', 'digits:16'],
            'nama_lengkap' => ['required', 'string', 'max:255'],
            'tanggal_lahir' => ['nullable', 'date'],
            'jenis_kelamin' => ['nullable', 'in:male,female'],
            'golongan_darah' => ['nullable', 'in:A,B,AB,O,unknown'],
            'nik_ayah' => ['nullable', 'digits:16'],
            'nik_ibu' => ['nullable', 'digits:16'],
        ];
    }

    private function readSpreadsheetRows(string $path): array
    {
        try {
            $reader = IOFactory::createReaderForFile($path);
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($path);
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = [];

            foreach ($worksheet->toArray('', true, true, false) as $row) {
                $rows[] = array_map(
                    static fn ($value): string => trim((string) $value),
                    $row,
                );
            }

            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);

            return $rows;
        } catch (\Throwable $e) {
            throw new RuntimeException('File Excel tidak dapat dibaca: '.$e->getMessage(), 0, $e);
        }
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
            $item['_duplicate'] = false;

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
            ->only(array_values($this->headers()))
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
