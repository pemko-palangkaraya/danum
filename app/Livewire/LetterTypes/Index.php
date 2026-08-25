<?php

declare(strict_types=1);

namespace App\Livewire\LetterTypes;

use App\Enums\LetterTypeStatus;
use App\Models\LetterType;
use App\Services\DocxTemplateService;
use App\Services\LetterTypeService;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Index extends Component
{
    use WithFileUploads;
    use WithPagination;

    public string $search = '';
    public string $filter = 'active';
    public int $perPage = 5;
    public bool $showForm = false;
    public ?string $editingId = null;
    public string $code = '';
    public string $name = '';
    public string $description = '';
    public string $status = 'draft';
    public string $validity_period = 'none';
    public string $variables_input = '';
    public $template_file = null;

    public function mount(): void { $this->authorize('viewAny', LetterType::class); }
    public function updatedSearch(): void { $this->resetPage(); }
    public function updatedFilter(): void { $this->resetPage(); }

    public function create(): void
    {
        $this->authorize('create', LetterType::class);
        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(string $id): void
    {
        $letterType = LetterType::query()->findOrFail($id);
        $this->authorize('update', $letterType);
        $this->editingId = $letterType->id;
        $this->code = $letterType->code;
        $this->name = $letterType->name;
        $this->description = (string) $letterType->description;
        $this->status = $letterType->status->value;
        $this->validity_period = (string) ($letterType->validity_period ?: ($letterType->has_expiry ? $this->legacyValidityPeriod($letterType->validity_days) : 'none'));
        $this->variables_input = implode("\n", $letterType->variables ?? []);
        $this->template_file = null;
        $this->showForm = true;
    }

    public function save(LetterTypeService $service, DocxTemplateService $docx): void
    {
        $data = $this->validate([
            'code' => ['required', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'in:draft,validated,active,retired'],
            'validity_period' => ['required', 'in:none,1_week,2_weeks,1_month,3_months,6_months,1_year'],
            'variables_input' => ['required', 'string'],
            'template_file' => [$this->editingId ? 'nullable' : 'required', 'file', 'mimes:docx', 'max:10240'],
        ]);

        $letterType = $this->editingId ? LetterType::query()->findOrFail($this->editingId) : null;
        if ($letterType) $this->authorize('update', $letterType); else $this->authorize('create', LetterType::class);

        $declared = $docx->normalizeVariables($this->variables_input);
        if (!$declared) {
            $this->addError('variables_input', 'Isi minimal satu variabel, misalnya recipient_name.');
            return;
        }

        $templatePath = null;
        if ($this->template_file) {
            $templatePath = $this->template_file->getRealPath();
        } elseif ($letterType?->template_path) {
            $templatePath = Storage::disk('local')->path($letterType->template_path);
        }

        if ($templatePath) {
            $found = $docx->extractVariables($templatePath);
            $diff = $docx->compareVariables($declared, $found);
            if ($diff['unknown']) {
                $this->addError('template_file', 'Cross-check gagal. DOCX memiliki variabel yang belum kamu daftarkan pada field "Variabel Template": {{'.implode('}}, {{', $diff['unknown']).'}}. Tambahkan variabel tersebut ke daftar input lalu simpan kembali.');
                return;
            }
            if ($diff['missing']) {
                $this->addError('template_file', 'Cross-check gagal. Kamu mendaftarkan variabel yang tidak ditemukan di DOCX: {{'.implode('}}, {{', $diff['missing']).'}}. Hapus dari daftar input atau tambahkan ke DOCX.');
                return;
            }
        }

        $data['variables'] = $declared;
        $data['has_expiry'] = $this->validity_period !== 'none';
        $data['validity_days'] = $this->legacyValidityDays($this->validity_period);
        unset($data['variables_input'], $data['template_file']);
        $data['tenant_id'] = null;
        $data['body_template'] = null;

        if ($this->template_file) $data['template_path'] = $this->template_file->store('letter-templates', 'local');

        if ($letterType) {
            $service->update($letterType, $data);
            $message = 'Master jenis surat berhasil diperbarui. Template lama dipertahankan sebagai versi historis.';
        } else {
            $service->create($data);
            $message = 'Master jenis surat berhasil dibuat.';
        }

        $this->showForm = false;
        $this->resetForm();
        $this->dispatch('toast', type: 'success', message: $message);
    }

    public function delete(string $id, LetterTypeService $service): void
    {
        $letterType = LetterType::query()->findOrFail($id);
        $this->authorize('delete', $letterType);
        $service->delete($letterType);
        $this->dispatch('toast', type: 'success', message: 'Jenis surat dihapus.');
    }

    private function resetForm(): void
    {
        $this->reset(['editingId', 'code', 'name', 'description', 'variables_input', 'template_file']);
        $this->status = LetterTypeStatus::DRAFT->value;
        $this->validity_period = 'none';
    }

    private function legacyValidityDays(string $period): ?int
    {
        return match ($period) {
            '1_week' => 7,
            '2_weeks' => 14,
            '1_month' => 30,
            '3_months' => 90,
            '6_months' => 180,
            '1_year' => 365,
            default => null,
        };
    }

    private function legacyValidityPeriod(?int $days): string
    {
        return match ($days) {
            7 => '1_week',
            14 => '2_weeks',
            30 => '1_month',
            90 => '3_months',
            180 => '6_months',
            365 => '1_year',
            default => 'none',
        };
    }

    public function render()
    {
        $query = LetterType::query()->latest();
        if ($this->search !== '') $query->where(fn ($q) => $q->where('code', 'like', "%{$this->search}%")->orWhere('name', 'like', "%{$this->search}%"));
        if ($this->filter === 'deleted') $query->onlyTrashed(); else $query->where('status', LetterTypeStatus::from($this->filter));
        return view('livewire.pages.letter-types.index', ['letterTypes' => $query->paginate($this->perPage)]);
    }
}
