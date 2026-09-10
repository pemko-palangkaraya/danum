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
    public bool $showLetterheadPreview = false;

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
    public int $letterheadLine1Size = 15;
    public int $letterheadLine2Size = 13;
    public int $letterheadLine3Size = 11;
    public int $letterheadMetaSize = 8;
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

    public function previewLetterhead(): void
    {
        $this->authorize('viewProfile', $this->tenant);
        $this->validateLetterheadSettings();
        $this->showLetterheadPreview = true;
    }

    public function closeLetterheadPreview(): void
    {
        $this->showLetterheadPreview = false;
    }

    public function save(TenantProfileService $service, AuditLogService $auditLogService): void
    {
        $this->authorize('updateProfile', $this->tenant);

        $this->validateProfile();
        $this->validateLetterheadSettings();

        if ($this->logo) {
            $this->validate(['logo' => ['file', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048']]);
        }

        $oldValues = $this->profileValues($this->tenant);
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
            'letterhead_line1_size' => $this->letterheadLine1Size,
            'letterhead_line2_size' => $this->letterheadLine2Size,
            'letterhead_line3_size' => $this->letterheadLine3Size,
            'letterhead_meta_size' => $this->letterheadMetaSize,
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

        $newValues = $this->profileValues($this->tenant);
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
        return view('livewire.pages.tenant-profile', ['logoUrl' => $this->currentLogo]);
    }

    private function validateProfile(): void
    {
        Validator::make(
            [
                'name' => $this->name, 'province' => $this->province, 'city' => $this->city,
                'district' => $this->district, 'village' => $this->village, 'address' => $this->address,
                'phone' => $this->phone, 'email' => $this->email, 'headName' => $this->headName,
                'headTitle' => $this->headTitle, 'headNip' => $this->headNip,
            ],
            [
                'name' => ['required', 'string', 'max:150'], 'province' => ['required', 'string', 'max:100'],
                'city' => ['required', 'string', 'max:100'], 'district' => ['required', 'string', 'max:100'],
                'village' => ['required', 'string', 'max:100'], 'address' => ['nullable', 'string'],
                'phone' => ['nullable', 'string', 'max:30'], 'email' => ['nullable', 'email', 'max:150'],
                'headName' => ['nullable', 'string', 'max:150'], 'headTitle' => ['nullable', 'string', 'max:100'],
                'headNip' => ['nullable', 'string', 'max:30'],
            ],
        )->validate();
    }

    private function validateLetterheadSettings(): void
    {
        Validator::make(
            [
                'letterheadLine1' => $this->letterheadLine1, 'letterheadLine2' => $this->letterheadLine2,
                'letterheadLine3' => $this->letterheadLine3, 'letterheadLine1Size' => $this->letterheadLine1Size,
                'letterheadLine2Size' => $this->letterheadLine2Size, 'letterheadLine3Size' => $this->letterheadLine3Size,
                'letterheadMetaSize' => $this->letterheadMetaSize, 'postalCode' => $this->postalCode,
                'website' => $this->website,
            ],
            [
                'letterheadLine1' => ['nullable', 'string', 'max:150'], 'letterheadLine2' => ['nullable', 'string', 'max:150'],
                'letterheadLine3' => ['nullable', 'string', 'max:150'],
                'letterheadLine1Size' => ['required', 'integer', 'min:8', 'max:36'],
                'letterheadLine2Size' => ['required', 'integer', 'min:8', 'max:36'],
                'letterheadLine3Size' => ['required', 'integer', 'min:8', 'max:36'],
                'letterheadMetaSize' => ['required', 'integer', 'min:6', 'max:18'],
                'postalCode' => ['nullable', 'string', 'max:10'], 'website' => ['nullable', 'url', 'max:255'],
            ],
        )->validate();
    }

    /** @return array<string,mixed> */
    private function profileValues(Tenant $tenant): array
    {
        return [
            'name' => $tenant->name, 'province' => $tenant->province, 'city' => $tenant->city,
            'district' => $tenant->district, 'village' => $tenant->village, 'address' => $tenant->address,
            'phone' => $tenant->phone, 'email' => $tenant->email, 'logo' => $tenant->logo,
            'head_name' => $tenant->head_name, 'head_title' => $tenant->head_title, 'head_nip' => $tenant->head_nip,
            'letterhead_line1' => $tenant->letterhead_line1, 'letterhead_line2' => $tenant->letterhead_line2,
            'letterhead_line3' => $tenant->letterhead_line3, 'letterhead_line1_size' => $tenant->letterhead_line1_size,
            'letterhead_line2_size' => $tenant->letterhead_line2_size, 'letterhead_line3_size' => $tenant->letterhead_line3_size,
            'letterhead_meta_size' => $tenant->letterhead_meta_size, 'postal_code' => $tenant->postal_code,
            'website' => $tenant->website,
        ];
    }

    private function fillForm(): void
    {
        $this->name = (string) $this->tenant->name; $this->province = (string) $this->tenant->province;
        $this->city = (string) $this->tenant->city; $this->district = (string) $this->tenant->district;
        $this->village = (string) $this->tenant->village; $this->address = (string) ($this->tenant->address ?? '');
        $this->phone = (string) ($this->tenant->phone ?? ''); $this->email = (string) ($this->tenant->email ?? '');
        $this->headName = (string) ($this->tenant->head_name ?? ''); $this->headTitle = (string) ($this->tenant->head_title ?? '');
        $this->headNip = (string) ($this->tenant->head_nip ?? '');
        $this->letterheadLine1 = (string) ($this->tenant->letterhead_line1 ?? '');
        $this->letterheadLine2 = (string) ($this->tenant->letterhead_line2 ?? '');
        $this->letterheadLine3 = (string) ($this->tenant->letterhead_line3 ?? '');
        $this->letterheadLine1Size = (int) ($this->tenant->letterhead_line1_size ?? 15);
        $this->letterheadLine2Size = (int) ($this->tenant->letterhead_line2_size ?? 13);
        $this->letterheadLine3Size = (int) ($this->tenant->letterhead_line3_size ?? 11);
        $this->letterheadMetaSize = (int) ($this->tenant->letterhead_meta_size ?? 8);
        $this->postalCode = (string) ($this->tenant->postal_code ?? ''); $this->website = (string) ($this->tenant->website ?? '');
    }
}
