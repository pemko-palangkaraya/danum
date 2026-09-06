<?php

declare(strict_types=1);

namespace App\Livewire\LetterVariableDefinitions;

use App\Livewire\Concerns\WithStandardTablePagination;
use App\Models\LetterVariableDefinition;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Index extends Component
{
    use WithStandardTablePagination;

    public string $search = '';
    public bool $showForm = false;
    public ?string $editingId = null;
    public string $key = '';
    public string $label = '';
    public string $type = 'text';
    public string $source = 'manual';
    public bool $required = false;
    public bool $readonly = false;
    public string $options_input = '';
    public string $description = '';
    public bool $is_active = true;

    private const TYPES = ['text', 'textarea', 'number', 'date', 'time', 'datetime', 'select', 'checkbox', 'email', 'tel'];
    private const SOURCES = ['manual', 'citizen', 'family', 'calculated', 'system'];

    public function mount(): void
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);
    }

    public function updatedSearch(): void { $this->resetPage(); }

    public function create(): void
    {
        $this->authorizeAccess();
        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(string $id): void
    {
        $this->authorizeAccess();
        $definition = LetterVariableDefinition::query()->findOrFail($id);
        $this->editingId = $definition->id;
        $this->key = $definition->key;
        $this->label = $definition->label;
        $this->type = $definition->type;
        $this->source = $definition->source;
        $this->required = $definition->required;
        $this->readonly = $definition->readonly;
        $this->options_input = collect($definition->options ?? [])->map(fn ($option) => ($option['value'] ?? '') . '|' . ($option['label'] ?? ''))->implode("\n");
        $this->description = (string) $definition->description;
        $this->is_active = $definition->is_active;
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->authorizeAccess();
        $this->validate([
            'key' => ['required', 'string', 'max:100', 'regex:/^[A-Za-z_][A-Za-z0-9_]*$/', Rule::unique('letter_variable_definitions', 'key')->ignore($this->editingId)],
            'label' => ['required', 'string', 'max:150'],
            'type' => ['required', Rule::in(self::TYPES)],
            'source' => ['required', Rule::in(self::SOURCES)],
            'options_input' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
            'required' => ['boolean'],
            'readonly' => ['boolean'],
            'is_active' => ['boolean'],
        ]);

        if ($this->type === 'select' && blank(trim($this->options_input))) {
            $this->addError('options_input', 'Pilihan wajib diisi untuk tipe Select.');
            return;
        }

        $options = $this->parseOptions();
        $data = [
            'key' => trim($this->key),
            'label' => trim($this->label),
            'type' => $this->type,
            'source' => $this->source,
            'required' => $this->required,
            'readonly' => $this->readonly,
            'options' => $options ?: null,
            'description' => filled(trim($this->description)) ? trim($this->description) : null,
            'is_active' => $this->is_active,
        ];

        if ($this->editingId) {
            LetterVariableDefinition::query()->findOrFail($this->editingId)->update($data);
            $message = 'Definisi variabel berhasil diperbarui.';
        } else {
            $data['sort_order'] = (int) LetterVariableDefinition::query()->max('sort_order') + 1;
            LetterVariableDefinition::query()->create($data);
            $message = 'Definisi variabel berhasil dibuat.';
        }

        $this->showForm = false;
        $this->resetForm();
        $this->dispatch('toast', type: 'success', message: $message);
    }

    public function delete(string $id): void
    {
        $this->authorizeAccess();
        $definition = LetterVariableDefinition::query()->findOrFail($id);
        $definition->delete();
        $this->dispatch('toast', type: 'success', message: 'Definisi variabel dihapus.');
    }

    private function parseOptions(): array
    {
        if ($this->type !== 'select') return [];
        return collect(preg_split('/\r?\n/', trim($this->options_input)) ?: [])
            ->map(function (string $line): ?array {
                $parts = array_map('trim', explode('|', $line, 2));
                if (blank($parts[0] ?? null)) return null;
                return ['value' => $parts[0], 'label' => $parts[1] ?? $parts[0]];
            })
            ->filter()
            ->values()
            ->all();
    }

    private function resetForm(): void
    {
        $this->reset(['editingId', 'key', 'label', 'options_input', 'description']);
        $this->type = 'text';
        $this->source = 'manual';
        $this->required = false;
        $this->readonly = false;
        $this->is_active = true;
        $this->resetValidation();
    }

    private function authorizeAccess(): void
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);
    }

    public function render()
    {
        $query = LetterVariableDefinition::query()->orderBy('sort_order')->orderBy('key');
        if ($this->search !== '') {
            $value = '%' . trim($this->search) . '%';
            $query->where(fn ($q) => $q->where('key', 'like', $value)->orWhere('label', 'like', $value));
        }

        return view('livewire.pages.letter-variable-definitions.index', [
            'definitions' => $query->paginate($this->perPage),
            'types' => self::TYPES,
            'sources' => self::SOURCES,
        ]);
    }
}
