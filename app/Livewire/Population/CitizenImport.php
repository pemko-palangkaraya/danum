<?php

declare(strict_types=1);

namespace App\Livewire\Population;

use App\Models\Citizen;
use App\Models\Tenant;
use App\Services\LibreOfficeSpreadsheetService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
class CitizenImport extends Component
{
    use WithFileUploads;

    public $file;
    public ?string $selectedTenantId = null;
    public string $duplicateMode = 'skip';
    public array $rows = [];
    public array $importErrors = [];
    public int $validCount = 0;
    public int $invalidCount = 0;
    public bool $ready = false;

    public function mount(): void
    {
        abort_unless(auth()->user()?->hasPermission('population.manage'), 403);

        if (! auth()->user()->isSuperAdmin()) {
            abort_unless(auth()->user()->tenant_id, 403);
            $this->selectedTenantId = auth()->user()->tenant_id;
        }
    }

    public function updatedSelectedTenantId(): void
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);
        $this->resetPreview();
    }

    public function updatedFile(): void
    {
        $this->preview();
    }

    private function tenantId(): string
    {
        $id = auth()->user()->isSuperAdmin() ? $this->selectedTenantId : auth()->user()->tenant_id;
        abort_unless($id && Tenant::whereKey($id)->exists(), 422, 'Tenant belum dipilih.');
        return (string) $id;
    }

    private function headers(): array
    {
        return [
            'nik' => 'nik', 'nama lengkap' => 'nama_lengkap', 'tempat lahir' => 'tempat_lahir',
            'tanggal lahir' => 'tanggal_lahir', 'jenis kelamin' => 'jenis_kelamin',
            'golongan darah' => 'golongan_darah', 'agama' => 'agama', 'status perkawinan' => 'status_perkawinan',
            'pendidikan' => 'pendidikan', 'pekerjaan' => 'pekerjaan', 'kewarganegaraan' => 'kewarganegaraan',
            'no passport' => 'no_passport', 'no kitap' => 'no_kitap', 'nama ayah' => 'nama_ayah',
            'nik ayah' => 'nik_ayah', 'nama ibu' => 'nama_ibu', 'nik ibu' => 'nik_ibu',
            'status kependudukan' => 'status_kependudukan',
        ];
    }

    public function preview(): void
    {
        abort_unless(auth()->user()?->hasPermission('population.manage'), 403);
        $this->resetPreview(false);
        if (! $this->file) return;

        $this->validate(['file' => ['required', 'file', 'mimes:xlsx,xls,csv,txt', 'max:10240']]);
        $tenantId = $this->tenantId();
        $path = $this->file->getRealPath();
        $extension = strtolower($this->file->getClientOriginalExtension());
        $temporaryCsv = null;

        try {
            if (in_array($extension, ['xlsx', 'xls'], true)) {
                $directory = storage_path('app'.DIRECTORY_SEPARATOR.'tmp'.DIRECTORY_SEPARATOR.'population-import');
                $temporaryCsv = app(LibreOfficeSpreadsheetService::class)->spreadsheetToCsv(
                    $path, $directory, 'population-import-'.bin2hex(random_bytes(6)).'.csv'
                );
                $path = $temporaryCsv;
            }

            $handle = fopen($path, 'rb');
            if ($handle === false) throw new \RuntimeException('File import tidak dapat dibaca.');

            $raw = [];
            while (($row = fgetcsv($handle, 0, ';')) !== false) {
                if (count($row) === 1 && str_contains((string) $row[0], ',')) $row = str_getcsv($row[0], ',');
                $raw[] = $row;
            }
            fclose($handle);

            if (count($raw) < 2) { $this->importErrors = ['File tidak memiliki baris data.']; return; }

            $headerMap = [];
            foreach (array_shift($raw) as $index => $header) {
                $header = preg_replace('/^\xEF\xBB\xBF/', '', (string) $header);
                $headerMap[strtolower(trim($header))] = $index;
            }

            foreach (['nik', 'nama lengkap'] as $required) {
                if (! array_key_exists($required, $headerMap)) $this->importErrors[] = 'Kolom wajib tidak ditemukan: '.strtoupper($required).'.';
            }
            if ($this->importErrors) return;

            $this->rows = [];
            $seen = [];
            foreach ($raw as $index => $rawRow) {
                $item = ['line' => $index + 2];
                foreach ($this->headers() as $source => $target) {
                    $item[$target] = isset($headerMap[$source]) ? trim((string) ($rawRow[$headerMap[$source]] ?? '')) : '';
                }
                $item['kewarganegaraan'] = $item['kewarganegaraan'] ?: 'WNI';
                $item['status_kependudukan'] = $item['status_kependudukan'] ?: 'active';
                $item['_error'] = null;
                if ($item['nik'] === '' && $item['nama_lengkap'] === '') continue;
                $this->rows[] = $item;
                if ($item['nik'] !== '') $seen[$item['nik']][] = count($this->rows) - 1;
            }

            foreach ($seen as $indexes) {
                if (count($indexes) > 1) foreach ($indexes as $i) $this->rows[$i]['_error'] = 'NIK duplikat di dalam file.';
            }

            $niks = array_values(array_unique(array_filter(array_column($this->rows, 'nik'))));
            $existingNiks = $niks ? Citizen::where('tenant_id', $tenantId)->whereIn('nik', $niks)->pluck('nik')->flip() : collect();

            foreach ($this->rows as $i => $item) {
                $validation = Validator::make($item, [
                    'nik' => ['required', 'digits:16'], 'nama_lengkap' => ['required', 'string', 'max:255'],
                    'tanggal_lahir' => ['nullable', 'date'], 'jenis_kelamin' => ['nullable', 'in:male,female'],
                    'golongan_darah' => ['nullable', 'in:A,B,AB,O,unknown'], 'nik_ayah' => ['nullable', 'digits:16'],
                    'nik_ibu' => ['nullable', 'digits:16'],
                ]);
                if ($validation->fails()) $this->rows[$i]['_error'] = implode(' ', $validation->errors()->all());
                if ($existingNiks->has($item['nik']) && $this->duplicateMode === 'skip') $this->rows[$i]['_error'] = 'NIK sudah ada — akan dilewati.';
            }

            $this->validCount = collect($this->rows)->whereNull('_error')->count();
            $this->invalidCount = count($this->rows) - $this->validCount;
            $this->ready = true;
        } finally {
            if ($temporaryCsv && is_file($temporaryCsv)) @unlink($temporaryCsv);
        }
    }

    public function import(): void
    {
        abort_unless(auth()->user()->hasPermission('population.manage'), 403);
        $tenantId = $this->tenantId();
        if (! $this->ready || ! $this->rows) { $this->preview(); return; }

        $count = 0;
        DB::transaction(function () use ($tenantId, &$count): void {
            $niks = array_values(array_unique(array_filter(array_column($this->rows, 'nik'))));
            $existing = Citizen::where('tenant_id', $tenantId)->whereIn('nik', $niks)->get()->keyBy('nik');
            $creates = [];
            $now = now();
            $userId = auth()->id();

            foreach ($this->rows as $row) {
                $citizen = $existing->get($row['nik']);
                if ($citizen) {
                    if ($this->duplicateMode === 'update' && $row['_error'] === 'NIK sudah ada — akan dilewati.') {
                        $this->persist($citizen, $row, $tenantId);
                        $count++;
                    }
                    continue;
                }
                if ($row['_error']) continue;
                $data = $this->cleanRow($row, $tenantId);
                $data['id'] = (string) Str::uuid();
                $data['created_by'] = $userId;
                $data['updated_by'] = $userId;
                $data['created_at'] = $now;
                $data['updated_at'] = $now;
                $creates[] = $data;
                $count++;
            }
            foreach (array_chunk($creates, 500) as $chunk) if ($chunk) Citizen::insert($chunk);
        });

        $this->dispatch('toast', type: 'success', message: "Import selesai: {$count} data warga berhasil diproses.");
        $this->resetPreview();
    }

    private function cleanRow(array $row, string $tenantId): array
    {
        return collect($row)->except(['line', '_error'])->merge(['tenant_id' => $tenantId])->toArray();
    }

    private function persist(?Citizen $existing, array $row, string $tenantId): void
    {
        $data = $this->cleanRow($row, $tenantId);
        $data['updated_by'] = auth()->id();
        if ($existing) $existing->update($data);
    }

    public function resetPreview(bool $clearFile = true): void
    {
        if ($clearFile) $this->file = null;
        $this->rows = [];
        $this->importErrors = [];
        $this->validCount = 0;
        $this->invalidCount = 0;
        $this->ready = false;
    }

    public function render()
    {
        return view('livewire.pages.population.citizen-import', [
            'tenants' => auth()->user()->isSuperAdmin() ? Tenant::orderBy('name')->get(['id', 'name', 'code']) : collect(),
        ]);
    }
}
