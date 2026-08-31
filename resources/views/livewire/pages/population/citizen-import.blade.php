<?php

declare(strict_types=1);

use App\Models\Citizen;
use App\Models\Tenant;
use App\Services\LibreOfficeSpreadsheetService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Features\SupportFileUploads\WithFileUploads;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component {
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
        $id = auth()->user()->isSuperAdmin()
            ? $this->selectedTenantId
            : auth()->user()->tenant_id;

        abort_unless($id && Tenant::whereKey($id)->exists(), 422, 'Tenant belum dipilih.');

        return (string) $id;
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

    public function preview(): void
    {
        $this->resetPreview(false);

        if (! $this->file) {
            return;
        }

        $this->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv,txt', 'max:10240'],
        ]);

        $tenantId = $this->tenantId();
        $path = $this->file->getRealPath();
        $extension = strtolower($this->file->getClientOriginalExtension());
        $temporaryCsv = null;

        try {
            if (in_array($extension, ['xlsx', 'xls'], true)) {
                $directory = storage_path('app'.DIRECTORY_SEPARATOR.'tmp'.DIRECTORY_SEPARATOR.'population-import');
                $temporaryCsv = app(LibreOfficeSpreadsheetService::class)->spreadsheetToCsv(
                    $path,
                    $directory,
                    'population-import-'.bin2hex(random_bytes(6)).'.csv'
                );
                $path = $temporaryCsv;
            }

            $handle = fopen($path, 'rb');

            if ($handle === false) {
                throw new \RuntimeException('File import tidak dapat dibaca.');
            }

            $raw = [];
            while (($row = fgetcsv($handle, 0, ';')) !== false) {
                if (count($row) === 1 && str_contains((string) $row[0], ',')) {
                    $row = str_getcsv($row[0], ',');
                }
                $raw[] = $row;
            }
            fclose($handle);

            if (count($raw) < 2) {
                $this->importErrors = ['File tidak memiliki baris data.'];
                return;
            }

            $headerMap = [];
            foreach (array_shift($raw) as $index => $header) {
                $header = (string) $header;
                $header = preg_replace('/^\xEF\xBB\xBF/', '', $header);
                $headerMap[strtolower(trim($header))] = $index;
            }

            foreach (['nik', 'nama lengkap'] as $required) {
                if (! array_key_exists($required, $headerMap)) {
                    $this->importErrors[] = 'Kolom wajib tidak ditemukan: '.strtoupper($required).'.';
                }
            }

            if ($this->importErrors) {
                return;
            }

            $map = $this->headers();
            $this->rows = [];
            $seen = [];

            foreach ($raw as $index => $rawRow) {
                $item = ['line' => $index + 2];

                foreach ($map as $source => $target) {
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

                $this->rows[] = $item;

                if ($item['nik'] !== '') {
                    $seen[$item['nik']][] = count($this->rows) - 1;
                }
            }

            foreach ($seen as $indexes) {
                if (count($indexes) > 1) {
                    foreach ($indexes as $i) {
                        $this->rows[$i]['_error'] = 'NIK duplikat di dalam file.';
                    }
                }
            }

            $niks = array_values(array_unique(array_filter(array_column($this->rows, 'nik'))));
            $existingNiks = collect();

            if ($niks) {
                $existingNiks = Citizen::where('tenant_id', $tenantId)
                    ->whereIn('nik', $niks)
                    ->pluck('nik')
                    ->flip();
            }

            foreach ($this->rows as $i => $item) {
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
                    $this->rows[$i]['_error'] = implode(' ', $validation->errors()->all());
                }

                if ($existingNiks->has($item['nik']) && $this->duplicateMode === 'skip') {
                    $this->rows[$i]['_error'] = 'NIK sudah ada — akan dilewati.';
                }
            }

            $this->validCount = collect($this->rows)->whereNull('_error')->count();
            $this->invalidCount = count($this->rows) - $this->validCount;
            $this->ready = true;
        } finally {
            if ($temporaryCsv && is_file($temporaryCsv)) {
                @unlink($temporaryCsv);
            }
        }
    }

    public function import(): void
    {
        abort_unless(auth()->user()->hasPermission('population.manage'), 403);

        $tenantId = $this->tenantId();

        if (! $this->ready || ! $this->rows) {
            $this->preview();
            return;
        }

        $count = 0;

        DB::transaction(function () use ($tenantId, &$count): void {
            $niks = array_values(array_unique(array_filter(array_column($this->rows, 'nik'))));
            $existing = Citizen::where('tenant_id', $tenantId)
                ->whereIn('nik', $niks)
                ->get()
                ->keyBy('nik');

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
        });

        $this->dispatch('toast', type: 'success', message: "Import selesai: {$count} data warga berhasil diproses.");
        $this->resetPreview();
    }

    private function cleanRow(array $row, string $tenantId): array
    {
        return collect($row)
            ->except(['line', '_error'])
            ->merge(['tenant_id' => $tenantId])
            ->toArray();
    }

    private function persist(?Citizen $existing, array $row, string $tenantId): void
    {
        $data = $this->cleanRow($row, $tenantId);
        $data['updated_by'] = auth()->id();

        if ($existing) {
            $existing->update($data);
        }
    }

    public function resetPreview(bool $clearFile = true): void
    {
        if ($clearFile) {
            $this->file = null;
        }

        $this->rows = [];
        $this->importErrors = [];
        $this->validCount = 0;
        $this->invalidCount = 0;
        $this->ready = false;
    }

    public function with(): array
    {
        return [
            'tenants' => auth()->user()->isSuperAdmin()
                ? Tenant::orderBy('name')->get(['id', 'name', 'code'])
                : collect(),
        ];
    }
};
?>
<div class="space-y-6">
<div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between"><div><p class="text-sm font-medium text-slate-500">Kependudukan</p><h1 class="mt-1 text-2xl font-semibold tracking-tight text-slate-900">Import Data Warga</h1><p class="mt-1 text-sm text-slate-500">Impor Excel atau CSV dengan validasi sebelum disimpan.</p></div><a href="{{route(auth()->user()->isSuperAdmin()?'population.admin.citizens.index':'population.citizens.index')}}" class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50">Kembali</a></div>
<div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm space-y-5">@if(auth()->user()->isSuperAdmin())<div><label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Tenant</label><select wire:model.live="selectedTenantId" class="mt-2 w-full max-w-xl rounded-xl border border-slate-200 px-4 py-2.5 text-sm"><option value="">Pilih tenant...</option>@foreach($tenants as $tenant)<option value="{{$tenant->id}}">{{$tenant->name}}{{ $tenant->code?' ('.$tenant->code.')':'' }}</option>@endforeach</select></div>@endif
<div class="grid gap-5 md:grid-cols-2"><div><label class="text-sm font-medium text-slate-700">File Excel / CSV</label><input wire:model="file" type="file" accept=".xlsx,.xls,.csv" class="mt-2 block w-full rounded-xl border border-slate-200 bg-white p-2 text-sm"><p class="mt-1 text-xs text-slate-500">Maksimal 10 MB.</p>@error('file')<p class="mt-1 text-xs text-red-600">{{$message}}</p>@enderror</div><div><label class="text-sm font-medium text-slate-700">Jika NIK sudah ada</label><select wire:model.live="duplicateMode" class="mt-2 w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm"><option value="skip">Lewati data yang sudah ada</option><option value="update">Perbarui data yang sudah ada</option></select></div></div>
<div class="flex flex-wrap gap-3"><button wire:click="preview" class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800">Validasi & Preview</button><a href="{{route('population.citizens.template')}}" class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Download Template Excel</a></div></div>
@if($importErrors)<div class="rounded-2xl border border-red-200 bg-red-50 p-5 text-sm text-red-700"><ul class="list-disc space-y-1 pl-5">@foreach($importErrors as $error)<li>{{$error}}</li>@endforeach</ul></div>@endif
@if($ready)<div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden"><div class="flex flex-col gap-3 border-b border-slate-100 bg-slate-50/60 px-6 py-4 sm:flex-row sm:items-center sm:justify-between"><div><h2 class="text-base font-semibold text-slate-900">Preview Import</h2><p class="mt-1 text-sm text-slate-500">{{$validCount}} baris siap diimpor · {{$invalidCount}} baris perlu diperiksa.</p></div>@if($validCount)<button wire:click="import" class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800">Import {{$validCount}} Data</button>@endif</div><div class="overflow-x-auto"><table class="min-w-full divide-y divide-slate-200"><thead class="bg-white"><tr><th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Baris</th><th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">NIK</th><th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Nama</th><th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Status</th></tr></thead><tbody class="divide-y divide-slate-100">@foreach(array_slice($rows,0,50) as $row)<tr><td class="px-5 py-3 text-sm text-slate-500">{{$row['line']}}</td><td class="px-5 py-3 font-mono text-sm">{{$row['nik']}}</td><td class="px-5 py-3 text-sm font-medium">{{$row['nama_lengkap']}}</td><td class="px-5 py-3 text-sm">@if($row['_error'])<span class="text-red-600">{{$row['_error']}}</span>@else<span class="font-semibold text-emerald-600">Siap diimpor</span>@endif</td></tr>@endforeach</tbody></table></div>@if(count($rows)>50)<div class="border-t border-slate-100 px-6 py-3 text-xs text-slate-500">Menampilkan 50 baris pertama dari {{count($rows)}} baris.</div>@endif</div>@endif
</div>
