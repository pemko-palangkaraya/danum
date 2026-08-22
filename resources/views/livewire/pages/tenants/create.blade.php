<?php

use App\Enums\TenantStatus;
use App\Http\Requests\StoreTenantRequest;
use App\Models\Tenant;
use App\Services\TenantService;
use Illuminate\Support\Facades\Validator;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component {
    public string $code = '';
    public string $name = '';
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

        $validated = Validator::make(
            $tenantData,
            (new StoreTenantRequest())->rules(),
        )->validate();

        $admin = $this->validate([
            'admin_name' => ['required', 'string', 'max:150'],
            'admin_email' => ['required', 'email', 'max:150', 'unique:users,email'],
            'admin_password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $tenantService->createWithInitialUser($validated, [
            'name' => $admin['admin_name'],
            'email' => $admin['admin_email'],
            'password' => $admin['admin_password'],
        ]);

        $this->redirectRoute('tenants.index');
    }

    public function cancel(): void
    {
        $this->redirectRoute('tenants.index');
    }
};
?>

<div class="space-y-6">
    <div>
        <div class="flex items-center gap-3">
            <a href="{{ route('tenants.index') }}" class="rounded-lg p-1.5 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700" aria-label="Back to tenants">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-5 w-5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 18l-6-6 6-6" /></svg>
            </a>
            <div>
                <h1 class="text-2xl font-semibold tracking-tight text-slate-900">Add Tenant</h1>
                <p class="mt-1 text-sm text-slate-500">Tambahkan organisasi dan administrator awal ke DANUM.</p>
            </div>
        </div>
    </div>

    <form wire:submit="save" class="space-y-6">
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4 sm:px-6"><h2 class="text-sm font-semibold text-slate-900">Basic Information</h2><p class="mt-1 text-xs text-slate-500">Informasi dasar organisasi.</p></div>
            <div class="grid gap-5 p-5 sm:grid-cols-2 sm:p-6">
                <div><label class="block text-sm font-medium text-slate-700">Code</label><input wire:model="code" maxlength="50" placeholder="Contoh: MKB" class="mt-2 block w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm"><x-input-error :messages="$errors->get('code')" class="mt-1.5" /></div>
                <div><label class="block text-sm font-medium text-slate-700">Name</label><input wire:model="name" maxlength="150" placeholder="Nama organisasi" class="mt-2 block w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm"><x-input-error :messages="$errors->get('name')" class="mt-1.5" /></div>
            </div>
        </div>

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4 sm:px-6"><h2 class="text-sm font-semibold text-slate-900">Location</h2></div>
            <div class="grid gap-5 p-5 sm:grid-cols-2 lg:grid-cols-4 sm:p-6">
                <div><label class="block text-sm font-medium text-slate-700">Province</label><input wire:model="province" class="mt-2 block w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm"><x-input-error :messages="$errors->get('province')" class="mt-1.5" /></div>
                <div><label class="block text-sm font-medium text-slate-700">City</label><input wire:model="city" class="mt-2 block w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm"><x-input-error :messages="$errors->get('city')" class="mt-1.5" /></div>
                <div><label class="block text-sm font-medium text-slate-700">District</label><input wire:model="district" class="mt-2 block w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm"><x-input-error :messages="$errors->get('district')" class="mt-1.5" /></div>
                <div><label class="block text-sm font-medium text-slate-700">Village</label><input wire:model="village" class="mt-2 block w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm"><x-input-error :messages="$errors->get('village')" class="mt-1.5" /></div>
                <div class="sm:col-span-2 lg:col-span-4"><label class="block text-sm font-medium text-slate-700">Address</label><textarea wire:model="address" rows="3" class="mt-2 block w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm"></textarea><x-input-error :messages="$errors->get('address')" class="mt-1.5" /></div>
            </div>
        </div>

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4 sm:px-6"><h2 class="text-sm font-semibold text-slate-900">Contact & Leadership</h2></div>
            <div class="grid gap-5 p-5 sm:grid-cols-2 sm:p-6">
                <div><label class="block text-sm font-medium text-slate-700">Phone</label><input wire:model="phone" class="mt-2 block w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm"><x-input-error :messages="$errors->get('phone')" class="mt-1.5" /></div>
                <div><label class="block text-sm font-medium text-slate-700">Email</label><input type="email" wire:model="email" class="mt-2 block w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm"><x-input-error :messages="$errors->get('email')" class="mt-1.5" /></div>
                <div><label class="block text-sm font-medium text-slate-700">Head Name</label><input wire:model="head_name" class="mt-2 block w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm"><x-input-error :messages="$errors->get('head_name')" class="mt-1.5" /></div>
                <div><label class="block text-sm font-medium text-slate-700">Head Title</label><input wire:model="head_title" class="mt-2 block w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm"><x-input-error :messages="$errors->get('head_title')" class="mt-1.5" /></div>
            </div>
        </div>

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4 sm:px-6"><h2 class="text-sm font-semibold text-slate-900">Initial Administrator</h2><p class="mt-1 text-xs text-slate-500">Akun ini akan menjadi administrator tenant dan dapat mengelola Letter Types serta Outgoing Letters.</p></div>
            <div class="grid gap-5 p-5 sm:grid-cols-2 sm:p-6">
                <div><label class="block text-sm font-medium text-slate-700">Name</label><input wire:model="admin_name" class="mt-2 block w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm"><x-input-error :messages="$errors->get('admin_name')" class="mt-1.5" /></div>
                <div><label class="block text-sm font-medium text-slate-700">Email / Login</label><input type="email" wire:model="admin_email" class="mt-2 block w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm"><x-input-error :messages="$errors->get('admin_email')" class="mt-1.5" /></div>
                <div><label class="block text-sm font-medium text-slate-700">Password</label><input type="password" wire:model="admin_password" class="mt-2 block w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm"><x-input-error :messages="$errors->get('admin_password')" class="mt-1.5" /></div>
                <div><label class="block text-sm font-medium text-slate-700">Confirm Password</label><input type="password" wire:model="admin_password_confirmation" class="mt-2 block w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm"><x-input-error :messages="$errors->get('admin_password_confirmation')" class="mt-1.5" /></div>
            </div>
        </div>

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4 sm:px-6"><h2 class="text-sm font-semibold text-slate-900">Status</h2></div>
            <div class="p-5 sm:p-6"><select wire:model="status" class="block w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm sm:max-w-md"><option value="">Select status</option>@foreach (TenantStatus::cases() as $tenantStatus)<option value="{{ $tenantStatus->value }}">{{ $tenantStatus->label() }}</option>@endforeach</select><x-input-error :messages="$errors->get('status')" class="mt-1.5" /></div>
        </div>

        <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end"><button type="button" wire:click="cancel" class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700">Cancel</button><button type="submit" wire:loading.attr="disabled" class="inline-flex items-center justify-center rounded-xl bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white disabled:opacity-60"><span wire:loading.remove>Create Tenant & Administrator</span><span wire:loading>Creating...</span></button></div>
    </form>
</div>
