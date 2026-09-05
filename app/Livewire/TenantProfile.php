<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Tenant;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class TenantProfile extends Component
{
    public Tenant $tenant;

    public function mount(): void
    {
        $tenant = auth()->user()?->tenant;
        abort_unless($tenant, 404);
        $this->authorize('viewProfile', $tenant);
        $this->tenant = $tenant;
    }

    public function render()
    {
        return view('livewire.pages.tenant-profile', [
            'letterheadUrl' => $this->tenant->letterheadUrl(),
            'identityFields' => [
                ['label' => 'Nama Organisasi', 'value' => $this->tenant->name, 'wide' => true],
                ['label' => 'Telepon', 'value' => $this->tenant->phone],
                ['label' => 'Email', 'value' => $this->tenant->email, 'type' => 'email'],
            ],
            'addressFields' => [
                ['label' => 'Provinsi', 'value' => $this->tenant->province],
                ['label' => 'Kota/Kabupaten', 'value' => $this->tenant->city],
                ['label' => 'Kecamatan', 'value' => $this->tenant->district],
                ['label' => 'Kelurahan/Desa', 'value' => $this->tenant->village],
            ],
            'leadershipFields' => [
                ['label' => 'Nama Pimpinan', 'value' => $this->tenant->head_name],
                ['label' => 'Jabatan', 'value' => $this->tenant->head_title],
            ],
        ]);
    }
}
