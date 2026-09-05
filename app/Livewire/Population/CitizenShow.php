<?php

declare(strict_types=1);

namespace App\Livewire\Population;

use App\Models\Citizen;
use App\Services\CitizenService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class CitizenShow extends Component
{
    public Citizen $citizen;
    public string $alamat = '';
    public string $rt = '';
    public string $rw = '';
    public string $kelurahan = '';
    public string $kecamatan = '';
    public string $kabupaten_kota = '';
    public string $provinsi = '';
    public string $kode_pos = '';
    public string $jenis_alamat = 'domisili';
    public string $berlaku_mulai = '';

    public function mount(Citizen $citizen): void
    {
        $user = auth()->user();

        abort_unless($user?->hasPermission('population.view'), 403);
        abort_unless(
            $user->isSuperAdmin() || $user->tenant_id === $citizen->tenant_id,
            404
        );

        $this->citizen = app(CitizenService::class)->loadDetail($citizen);
    }

    public function updatedProvinsi(): void
    {
        $this->kabupaten_kota = '';
        $this->kecamatan = '';
        $this->kelurahan = '';
        $this->kode_pos = '';
    }

    public function updatedKabupatenKota(): void
    {
        $this->kecamatan = '';
        $this->kelurahan = '';
        $this->kode_pos = '';
    }

    public function updatedKecamatan(): void
    {
        $this->kelurahan = '';
        $this->kode_pos = '';
    }

    public function saveAddress(): void
    {
        abort_unless(auth()->user()?->hasPermission('population.manage'), 403);

        $service = app(CitizenService::class);
        $service->addAddress($this->citizen, $this->only([
            'alamat', 'rt', 'rw', 'kelurahan', 'kecamatan', 'kabupaten_kota',
            'provinsi', 'kode_pos', 'jenis_alamat', 'berlaku_mulai',
        ]));

        $this->citizen = $service->loadAddresses($this->citizen);
        $this->reset([
            'alamat', 'rt', 'rw', 'kelurahan', 'kecamatan', 'kabupaten_kota',
            'provinsi', 'kode_pos', 'berlaku_mulai',
        ]);
        $this->jenis_alamat = 'domisili';
        $this->dispatch('toast', type: 'success', message: 'Alamat warga berhasil ditambahkan.');
    }

    public function render(): View
    {
        $user = auth()->user();
        $isSuperAdmin = $user->isSuperAdmin();

        return view('livewire.pages.population.citizen-show', [
            'activeMembership' => $this->citizen->activeFamilyMembership,
            'canManage' => $user->hasPermission('population.manage'),
            'citizensRoute' => $isSuperAdmin
                ? 'population.admin.citizens.index'
                : 'population.citizens.index',
            'familiesRoute' => $isSuperAdmin
                ? 'population.admin.families.index'
                : 'population.families.index',
        ]);
    }
}
