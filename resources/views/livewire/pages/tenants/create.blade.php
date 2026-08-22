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

    public function save(TenantService $tenantService): void
    {
        $this->authorize('create', Tenant::class);

        $data = [
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
            $data,
            (new StoreTenantRequest())->rules(),
        )->validate();

        $tenantService->create($validated);

        $this->redirectRoute('tenants.index');
    }

    public function cancel(): void
    {
        $this->redirectRoute('tenants.index');
    }
};
?>

<div class="space-y-6">

    {{-- Page Header --}}
    <div>
        <div class="flex items-center gap-3">
            <a
                href="{{ route('tenants.index') }}"
                class="rounded-lg p-1.5 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700"
                aria-label="Back to tenants">
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    class="h-5 w-5">
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M15 18l-6-6 6-6" />
                </svg>
            </a>

            <div>
                <h1 class="text-2xl font-semibold tracking-tight text-slate-900">
                    Add Tenant
                </h1>

                <p class="mt-1 text-sm text-slate-500">
                    Tambahkan organisasi baru ke dalam DANUM.
                </p>
            </div>
        </div>
    </div>

    {{-- Form --}}
    <form
        wire:submit="save"
        class="space-y-6">

        {{-- Basic Information --}}
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

            <div class="border-b border-slate-200 px-5 py-4 sm:px-6">
                <h2 class="text-sm font-semibold text-slate-900">
                    Basic Information
                </h2>

                <p class="mt-1 text-xs text-slate-500">
                    Informasi dasar organisasi.
                </p>
            </div>

            <div class="grid gap-5 p-5 sm:grid-cols-2 sm:p-6">

                {{-- Code --}}
                <div>
                    <label
                        for="code"
                        class="block text-sm font-medium text-slate-700">
                        Code
                    </label>

                    <input
                        id="code"
                        type="text"
                        wire:model="code"
                        maxlength="50"
                        placeholder="Contoh: MKB"
                        class="mt-2 block w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-slate-400 focus:ring-2 focus:ring-slate-100">

                    @error('code')
                    <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Name --}}
                <div>
                    <label
                        for="name"
                        class="block text-sm font-medium text-slate-700">
                        Name
                    </label>

                    <input
                        id="name"
                        type="text"
                        wire:model="name"
                        maxlength="150"
                        placeholder="Nama organisasi"
                        class="mt-2 block w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-slate-400 focus:ring-2 focus:ring-slate-100">

                    @error('name')
                    <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

            </div>
        </div>

        {{-- Location --}}
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

            <div class="border-b border-slate-200 px-5 py-4 sm:px-6">
                <h2 class="text-sm font-semibold text-slate-900">
                    Location
                </h2>

                <p class="mt-1 text-xs text-slate-500">
                    Lokasi administratif organisasi.
                </p>
            </div>

            <div class="grid gap-5 p-5 sm:grid-cols-2 lg:grid-cols-4 sm:p-6">

                {{-- Province --}}
                <div>
                    <label
                        for="province"
                        class="block text-sm font-medium text-slate-700">
                        Province
                    </label>

                    <input
                        id="province"
                        type="text"
                        wire:model="province"
                        maxlength="100"
                        class="mt-2 block w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm outline-none transition focus:border-slate-400 focus:ring-2 focus:ring-slate-100">

                    @error('province')
                    <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- City --}}
                <div>
                    <label
                        for="city"
                        class="block text-sm font-medium text-slate-700">
                        City
                    </label>

                    <input
                        id="city"
                        type="text"
                        wire:model="city"
                        maxlength="100"
                        class="mt-2 block w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm outline-none transition focus:border-slate-400 focus:ring-2 focus:ring-slate-100">

                    @error('city')
                    <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- District --}}
                <div>
                    <label
                        for="district"
                        class="block text-sm font-medium text-slate-700">
                        District
                    </label>

                    <input
                        id="district"
                        type="text"
                        wire:model="district"
                        maxlength="100"
                        class="mt-2 block w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm outline-none transition focus:border-slate-400 focus:ring-2 focus:ring-slate-100">

                    @error('district')
                    <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Village --}}
                <div>
                    <label
                        for="village"
                        class="block text-sm font-medium text-slate-700">
                        Village
                    </label>

                    <input
                        id="village"
                        type="text"
                        wire:model="village"
                        maxlength="100"
                        class="mt-2 block w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm outline-none transition focus:border-slate-400 focus:ring-2 focus:ring-slate-100">

                    @error('village')
                    <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Address --}}
                <div class="sm:col-span-2 lg:col-span-4">
                    <label
                        for="address"
                        class="block text-sm font-medium text-slate-700">
                        Address
                    </label>

                    <textarea
                        id="address"
                        wire:model="address"
                        rows="3"
                        class="mt-2 block w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm outline-none transition focus:border-slate-400 focus:ring-2 focus:ring-slate-100"></textarea>

                    @error('address')
                    <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

            </div>
        </div>

        {{-- Contact & Leadership --}}
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

            <div class="border-b border-slate-200 px-5 py-4 sm:px-6">
                <h2 class="text-sm font-semibold text-slate-900">
                    Contact & Leadership
                </h2>

                <p class="mt-1 text-xs text-slate-500">
                    Informasi kontak dan pimpinan organisasi.
                </p>
            </div>

            <div class="grid gap-5 p-5 sm:grid-cols-2 sm:p-6">

                {{-- Phone --}}
                <div>
                    <label
                        for="phone"
                        class="block text-sm font-medium text-slate-700">
                        Phone
                    </label>

                    <input
                        id="phone"
                        type="text"
                        wire:model="phone"
                        maxlength="30"
                        class="mt-2 block w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm outline-none transition focus:border-slate-400 focus:ring-2 focus:ring-slate-100">

                    @error('phone')
                    <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Email --}}
                <div>
                    <label
                        for="email"
                        class="block text-sm font-medium text-slate-700">
                        Email
                    </label>

                    <input
                        id="email"
                        type="email"
                        wire:model="email"
                        maxlength="150"
                        class="mt-2 block w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm outline-none transition focus:border-slate-400 focus:ring-2 focus:ring-slate-100">

                    @error('email')
                    <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Head Name --}}
                <div>
                    <label
                        for="head_name"
                        class="block text-sm font-medium text-slate-700">
                        Head Name
                    </label>

                    <input
                        id="head_name"
                        type="text"
                        wire:model="head_name"
                        maxlength="150"
                        class="mt-2 block w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm outline-none transition focus:border-slate-400 focus:ring-2 focus:ring-slate-100">

                    @error('head_name')
                    <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Head Title --}}
                <div>
                    <label
                        for="head_title"
                        class="block text-sm font-medium text-slate-700">
                        Head Title
                    </label>

                    <input
                        id="head_title"
                        type="text"
                        wire:model="head_title"
                        maxlength="100"
                        class="mt-2 block w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm outline-none transition focus:border-slate-400 focus:ring-2 focus:ring-slate-100">

                    @error('head_title')
                    <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

            </div>
        </div>

        {{-- Status --}}
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

            <div class="border-b border-slate-200 px-5 py-4 sm:px-6">
                <h2 class="text-sm font-semibold text-slate-900">
                    Status
                </h2>
            </div>

            <div class="p-5 sm:p-6">
                <label
                    for="status"
                    class="block text-sm font-medium text-slate-700">
                    Tenant Status
                </label>

                <select
                    id="status"
                    wire:model="status"
                    class="mt-2 block w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm outline-none transition focus:border-slate-400 focus:ring-2 focus:ring-slate-100 sm:max-w-md">
                    <option value="">
                        Select status
                    </option>

                    @foreach (TenantStatus::cases() as $tenantStatus)
                    <option value="{{ $tenantStatus->value }}">
                        <!-- {{ str($tenantStatus->value)->replace('_', ' ')->title() }} -->
                        {{ $tenantStatus->label() }}
                    </option>
                    @endforeach
                </select>

                @error('status')
                <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        {{-- Actions --}}
        <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">

            <button
                type="button"
                wire:click="cancel"
                class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                Cancel
            </button>

            <button
                type="submit"
                wire:loading.attr="disabled"
                class="inline-flex items-center justify-center rounded-xl bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-60">
                <span wire:loading.remove>
                    Create Tenant
                </span>

                <span wire:loading>
                    Creating...
                </span>
            </button>

        </div>

    </form>
</div>