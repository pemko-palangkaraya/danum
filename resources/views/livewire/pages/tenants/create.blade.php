<?php

use App\Enums\TenantStatus;
use App\Http\Requests\StoreTenantRequest;
use App\Models\Tenant;
use App\Models\TenantCategory;
use App\Services\TenantService;
use Illuminate\Support\Facades\Validator;
use Livewire\Attributes\Layout;
use Livewire\WithFileUploads;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component {
    use WithFileUploads;

    public string $code = '';
    public string $name = '';
    public string $tenant_category_id = '';
    public string $province = '';
    public string $city = '';
    public string $district = '';
    public string $village = '';
    public string $address = '';
    public string $phone = '';
    public string $email = '';
    public string $logo = '';
    public string $head_name = '';
    public string $head_title = '';
    public string $status = '';
    public $letterhead = null;

    public string $admin_name = '';
    public string $admin_email = '';
    public string $admin_password = '';
    public string $admin_password_confirmation = '';

    public function save(TenantService $tenantService): void
    {
        $this->authorize('create', Tenant::class);

        $tenantData = [
            'code' => $this->code,
            'name' => $this->name,
            'tenant_category_id' => $this->tenant_category_id,
            'province' => $this->province,
            'city' => $this->city,
            'district' => $this->district,
            'village' => $this->village,
            'address' => $this->address,
            'phone' => $this->phone,
            'email' => $this->email,
            'logo' => $this->logo,
            'head_name' => $this->head_name,
            'head_title' => $this->head_title,
            'status' => $this->status,
        ];

        $validated = Validator::make($tenantData, (new StoreTenantRequest())->rules())->validate();

        $this->validate([
            'letterhead' => ['required', 'file', 'mimes:png,jpg,jpeg,webp', 'max:5120'],
        ]);

        $admin = $this->validate([
            'admin_name' => ['required', 'string', 'max:150'],
            'admin_email' => ['required', 'email', 'max:150', 'unique:users,email'],
            'admin_password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $validated['letterhead_path'] = $this->letterhead->store('tenant-letterheads', 'public');

        $tenantService->createWithInitialUser($validated, [
            'name' => $admin['admin_name'],
            'email' => $admin['admin_email'],
            'password' => $admin['admin_password'],
        ]);

        $this->dispatch('toast', type: 'success', message: 'Tenant dan administrator berhasil dibuat.');
        $this->redirectRoute('tenants.index');
    }

    public function cancel(): void
    {
        $this->redirectRoute('tenants.index');
    }
};
?>

<div class="space-y-6">
    <x-ui.page-header
        title="Add Tenant"
        description="Tambahkan organisasi, kop surat, dan administrator awal ke DANUM."
        :back-url="route('tenants.index')"
        back-label="Back to tenants"
    />

    <form wire:submit="save" class="space-y-6">
        @include('livewire.pages.tenants.partials.create-basic')
        @include('livewire.pages.tenants.partials.create-letterhead')
        @include('livewire.pages.tenants.partials.create-location')
        @include('livewire.pages.tenants.partials.create-contact')
        @include('livewire.pages.tenants.partials.create-administrator')
        @include('livewire.pages.tenants.partials.create-footer')
    </form>
</div>
