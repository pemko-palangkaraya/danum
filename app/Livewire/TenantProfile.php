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
    public string $headNip = '';

    public string $letterheadLine1 = '';
    public string $letterheadLine2 = '';
    public string $letterheadLine3 = '';
    public string $postalCode = '';
    public string $website = '';
    public $logo = null;
    public ?string $currentLogo = null;

    public function mount(): void
    {
        $tenant = auth()->user()?->tenant;
        abort_unless($tenant, 404);
        $this->authorize('viewProfile', $tenant);

        $this->tenant = $tenant;
        $this->canUpdate = auth()->user()?->can('updateProfile', $tenant) === true;
        $this->currentLogo = $tenant->logoUrl();
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
                'headNip' => $this->headNip,
                'letterheadLine1' => $this->letterheadLine1,
                'letterheadLine2' => $this->letterheadLine2,
                'letterheadLine3' => $this->letterheadLine3,
                'postalCode' => $this->postalCode,
                'website' => $this->website,
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
                'headNip' => ['nullable', 'string', 'max:30'],
                'letterheadLine1' => ['nullable', 'string', 'max:150'],
                'letterheadLine2' => ['nullable', 'string', 'max:150'],
                'letterheadLine3' => ['nullable', 'string', 'max:150'],
                'postalCode' => ['nullable', 'string', 'max:10'],
                'website' => ['nullable', 'url', 'max:255'],
            ],
        )->validate();

        if ($this->logo) {
            $this->validate([
                'logo' => ['file', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
            ]);
        }

        $oldValues = [
            'name' => $this->tenant->name,
            'province' => $this->tenant->province,
            'city' => $this->tenant->city,
            'district' => $this->tenant->district,
            'village' => $this->tenant->village,
            'address' => $this->tenant->address,
            'phone' => $this->tenant->phone,
            'email' => $this->tenant->email,
            'logo' => $this->tenant->logo,
            'head_name' => $this->tenant->head_name,
            'head_title' => $this->tenant->head_title,
            'head_nip' => $this->tenant->head_nip,
            'letterhead_line1' => $this->tenant->letterhead_line1,
            'letterhead_line2' => $this->tenant->letterhead_line2,
            'letterhead_line3' => $this->tenant->letterhead_line3,
            'postal_code' => $this->tenant->postal_code,
            'website' => $this->tenant->website,
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
            'head_nip' => $this->headNip,
            'letterhead_line1' => $this->letterheadLine1,
            'letterhead_line2' => $this->letterheadLine2,
            'letterhead_line3' => $this->letterheadLine3,
            'postal_code' => $this->postalCode,
            'website' => $this->website,
        ];

        if ($this->logo) {
            $oldLogo = $this->tenant->logo;
            $newLogo = $this->logo->store('tenant-logos', 'public');
            $data['logo'] = $newLogo;

            if ($oldLogo && ! str_starts_with($oldLogo, 'http://') && ! str_starts_with($oldLogo, 'https://') && ! str_starts_with($oldLogo, '/')) {
                Storage::disk('public')->delete($oldLogo);
            }
        }

        $updated = $service->update($this->tenant, $data);

        $this->tenant = $updated->fresh();
        $this->logo = null;
        $this->currentLogo = $this->tenant->logoUrl();
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
            'logo' => $this->tenant->logo,
            'head_name' => $this->tenant->head_name,
            'head_title' => $this->tenant->head_title,
            'head_nip' => $this->tenant->head_nip,
            'letterhead_line1' => $this->tenant->letterhead_line1,
            'letterhead_line2' => $this->tenant->letterhead_line2,
            'letterhead_line3' => $this->tenant->letterhead_line3,
            'postal_code' => $this->tenant->postal_code,
            'website' => $this->tenant->website,
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
            'logoUrl' => $this->currentLogo,
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
        $this->headNip = (string) ($this->tenant->head_nip ?? '');
        $this->letterheadLine1 = (string) ($this->tenant->letterhead_line1 ?? '');
        $this->letterheadLine2 = (string) ($this->tenant->letterhead_line2 ?? '');
        $this->letterheadLine3 = (string) ($this->tenant->letterhead_line3 ?? '');
        $this->postalCode = (string) ($this->tenant->postal_code ?? '');
        $this->website = (string) ($this->tenant->website ?? '');
    }
}
