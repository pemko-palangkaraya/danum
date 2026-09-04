<?php

declare(strict_types=1);

namespace App\Livewire\Population;

use App\Services\CitizenImportService;
use App\Services\LibreOfficeSpreadsheetService;
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

    public function updatedDuplicateMode(): void
    {
        if ($this->file) {
            $this->preview();
        } else {
            $this->resetPreview(false);
        }
    }

    public function updatedFile(): void
    {
        $this->preview();
    }

    private function tenantId(): string
    {
        $id = auth()->user()->isSuperAdmin() ? $this->selectedTenantId : auth()->user()->tenant_id;
        abort_unless($id, 422, 'Tenant belum dipilih.');
        abort_unless(app(CitizenImportService::class)->tenantExists((string) $id), 422, 'Tenant belum dipilih.');

        return (string) $id;
    }

    public function preview(): void
    {
        abort_unless(auth()->user()?->hasPermission('population.manage'), 403);
        $this->resetPreview(false);
        if (! $this->file) {
            return;
        }

        $this->validate(['file' => ['required', 'file', 'mimes:xlsx,xls,csv,txt', 'max:10240']]);

        $result = app(CitizenImportService::class)->preview(
            $this->file,
            $this->tenantId(),
            $this->duplicateMode,
            app(LibreOfficeSpreadsheetService::class),
        );

        $this->rows = $result['rows'];
        $this->importErrors = $result['errors'];
        $this->validCount = $result['validCount'];
        $this->invalidCount = $result['invalidCount'];
        $this->ready = true;
    }

    public function import(): void
    {
        abort_unless(auth()->user()?->hasPermission('population.manage'), 403);
        $tenantId = $this->tenantId();

        if (! $this->ready || ! $this->rows) {
            $this->preview();
            return;
        }

        $count = app(CitizenImportService::class)->import(
            $this->rows,
            $tenantId,
            $this->duplicateMode,
            auth()->id(),
        );

        $this->dispatch('toast', type: 'success', message: "Import selesai: {$count} data warga berhasil diproses.");
        $this->resetPreview();
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

    public function render()
    {
        return view('livewire.pages.population.citizen-import', [
            'tenants' => auth()->user()->isSuperAdmin()
                ? app(CitizenImportService::class)->tenants()
                : collect(),
        ]);
    }
}
