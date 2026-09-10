<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Tenant;
use App\Services\AuditLogService;
use App\Services\TenantProfileService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
class TenantProfile extends Component
{
    use WithFileUploads;

    public Tenant $tenant;
    public bool $canUpdate = false;

    public string $name = '';
    public string $province = '';
    public string $city = '';
    public string $district = '';
    public string $village = '';
    public string $address = '';
    public string $phone = '';
    public string $email = '';
    public string $headName = '';
    public string $headTitle = '';
    public $letterhead = null;
    public ?string $currentLetterhead = null;

    public function mount(): void
    {
        $tenant = auth()->user()?->tenant;
        abort_unless($tenant, 404);
        $this->authorize('viewProfile', $tenant);

        $this->tenant = $tenant;
        $this->canUpdate = auth()->user()?->can('updateProfile', $tenant) === true;
        $this->currentLetterhead = $tenant->letterheadUrl();
        $this->fillForm();
    }

    public function save(TenantProfileService $service, AuditLogService $auditLogService): void
    {
        $this->authorize('updateProfile', $this->tenant);

        Validator::make(
            [
                'name' => $this->name,
                'province' => $this->province,
                'city' => $this->city,
                'district' => $this->district,
                'village' => $this->village,
                'address' => $this->address,
                'phone' => $this->phone,
                'email' => $this->email,
                'headName' => $this->headName,
                'headTitle' => $this->headTitle,
            ],
            [
                'name' => ['required', 'string', 'max:150'],
                'province' => ['required', 'string', 'max:100'],
                'city' => ['required', 'string', 'max:100'],
                'district' => ['required', 'string', 'max:100'],
                'village' => ['required', 'string', 'max:100'],
                'address' => ['nullable', 'string'],
                'phone' => ['nullable', 'string', 'max:30'],
                'email' => ['nullable', 'email', 'max:150'],
                'headName' => ['nullable', 'string', 'max:150'],
                'headTitle' => ['nullable', 'string', 'max:100'],
            ],
        )->validate();

        $oldValues = [
            'name' => $this->tenant->name,
            'province' => $this->tenant->province,
            'city' => $this->tenant->city,
            'district' => $this->tenant->district,
            'village' => $this->tenant->village,
            'address' => $this->tenant->address,
            'phone' => $this->tenant->phone,
            'email' => $this->tenant->email,
            'head_name' => $this->tenant->head_name,
            'head_title' => $this->tenant->head_title,
            'letterhead_path' => $this->tenant->letterhead_path,
        ];

        $data = [
            'name' => $this->name,
            'province' => $this->province,
            'city' => $this->city,
            'district' => $this->district,
            'village' => $this->village,
            'address' => $this->address,
            'phone' => $this->phone,
            'email' => $this->email,
            'head_name' => $this->headName,
            'head_title' => $this->headTitle,
        ];

        if ($this->letterhead) {
            $this->validate([
                'letterhead' => ['file', 'image', 'mimes:png,jpg,jpeg,webp', 'max:4096'],
            ]);

            $oldLetterhead = $this->tenant->letterhead_path;
            $newLetterhead = $this->letterhead->store('tenant-letterheads', 'public');
            $data['letterhead_path'] = $newLetterhead;

            if ($oldLetterhead && $oldLetterhead !== $newLetterhead) {
                Storage::disk('public')->delete($oldLetterhead);
            }
        }

        $updated = $service->update($this->tenant, $data);

        $this->tenant = $updated->fresh();
        $this->letterhead = null;
        $this->currentLetterhead = $this->tenant->letterheadUrl();
        $this->fillForm();

        $newValues = [
            'name' => $this->tenant->name,
            'province' => $this->tenant->province,
            'city' => $this->tenant->city,
            'district' => $this->tenant->district,
            'village' => $this->tenant->village,
            'address' => $this->tenant->address,
            'phone' => $this->tenant->phone,
            'email' => $this->tenant->email,
            'head_name' => $this->tenant->head_name,
            'head_title' => $this->tenant->head_title,
            'letterhead_path' => $this->tenant->letterhead_path,
        ];

        if ($oldValues !== $newValues) {
            $auditLogService->record(
                action: 'tenant.profile.updated',
                user: auth()->user(),
                auditable: $this->tenant,
                oldValues: $oldValues,
                newValues: $newValues,
                tenantId: $this->tenant->id,
            );
        }

        $this->dispatch('toast', type: 'success', message: 'Profil organisasi berhasil diperbarui.');
    }

    public function render()
    {
        return view('livewire.pages.tenant-profile', [
            'letterheadUrl' => $this->currentLetterhead,
        ]);
    }

    private function fillForm(): void
    {
        $this->name = (string) $this->tenant->name;
        $this->province = (string) $this->tenant->province;
        $this->city = (string) $this->tenant->city;
        $this->district = (string) $this->tenant->district;
        $this->village = (string) $this->tenant->village;
        $this->address = (string) ($this->tenant->address ?? '');
        $this->phone = (string) ($this->tenant->phone ?? '');
        $this->email = (string) ($this->tenant->email ?? '');
        $this->headName = (string) ($this->tenant->head_name ?? '');
        $this->headTitle = (string) ($this->tenant->head_title ?? '');
    }
}
