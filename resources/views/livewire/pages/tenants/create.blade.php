<?php

use App\Enums\TenantStatus;
use App\Http\Requests\StoreTenantRequest;
use App\Models\Tenant;
use App\Services\TenantService;
use Illuminate\Support\Facades\Validator;
use Livewire\Attributes\Layout;
use Livewire\WithFileUploads;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component {
    use WithFileUploads;

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
                <p class="mt-1 text-sm text-slate-500">Tambahkan organisasi, kop surat, dan administrator awal ke DANUM.</p>
            </div>
        </div>
    </div>

    <form wire:submit="save" class="space-y-6">
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4 sm:px-6"><h2 class="text-sm font-semibold text-slate-900">Basic Information</h2><p class="mt-1 text-xs text-slate-500">Informasi dasar organisasi.</p></div>
            <div class="grid gap-5 p-5 sm:grid-cols-2 sm:p-6">
                <div><label class="block text-sm font-medium text-slate-700">Code</label><input wire:model="code" maxlength="50" placeholder="Contoh: MKB" class="mt-2 block w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm">@error('code')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                <div><label class="block text-sm font-medium text-slate-700">Name</label><input wire:model="name" maxlength="150" placeholder="Nama organisasi" class="mt-2 block w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm">@error('name')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror</div>
            </div>
        </div>

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4 sm:px-6"><h2 class="text-sm font-semibold text-slate-900">Letterhead / Kop Surat</h2><p class="mt-1 text-xs text-slate-500">Kop ini akan digunakan oleh seluruh surat yang dibuat tenant ini.</p></div>
            <div class="grid gap-5 p-5 sm:grid-cols-[minmax(0,1fr)_280px] sm:p-6">
                <div>
                    <label class="block text-sm font-medium text-slate-700">Upload Kop Surat</label>
                    <input type="file" wire:model="letterhead" accept=".png,.jpg,.jpeg,.webp" class="mt-2 block w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm">
                    <p class="mt-1.5 text-xs text-slate-500">PNG, JPG, JPEG, atau WEBP. Maksimal 5 MB.</p>
                    @error('letterhead')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div class="rounded-xl border border-dashed border-slate-200 bg-slate-50 p-3">
                    @if ($letterhead)
                        <img src="{{ $letterhead->temporaryUrl() }}" alt="Letterhead preview" class="max-h-32 w-full object-contain">
                    @else
                        <div class="flex h-24 items-center justify-center text-center text-xs text-slate-400">Preview kop surat akan tampil di sini</div>
                    @endif
                </div>
            </div>
        </div>

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4 sm:px-6"><h2 class="text-sm font-semibold text-slate-900">Location</h2></div>
            <div class="grid gap-5 p-5 sm:grid-cols-2 lg:grid-cols-4 sm:p-6">
                @foreach (['province'=>'Province','city'=>'City','district'=>'District','village'=>'Village'] as $field => $label)
                    <div><label class="block text-sm font-medium text-slate-700">{{ $label }}</label><input wire:model="{{ $field }}" class="mt-2 block w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm">@error($field)<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                @endforeach
                <div class="sm:col-span-2 lg:col-span-4"><label class="block text-sm font-medium text-slate-700">Address</label><textarea wire:model="address" rows="3" class="mt-2 block w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm"></textarea>@error('address')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror</div>
            </div>
        </div>

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4 sm:px-6"><h2 class="text-sm font-semibold text-slate-900">Contact & Leadership</h2></div>
            <div class="grid gap-5 p-5 sm:grid-cols-2 sm:p-6">
                @foreach (['phone'=>'Phone','email'=>'Email','head_name'=>'Head Name','head_title'=>'Head Title'] as $field => $label)
                    <div><label class="block text-sm font-medium text-slate-700">{{ $label }}</label><input type="{{ $field === 'email' ? 'email' : 'text' }}" wire:model="{{ $field }}" class="mt-2 block w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm">@error($field)<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                @endforeach
            </div>
        </div>

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4 sm:px-6"><h2 class="text-sm font-semibold text-slate-900">Initial Administrator</h2><p class="mt-1 text-xs text-slate-500">Akun ini menjadi administrator tenant.</p></div>
            <div class="grid gap-5 p-5 sm:grid-cols-2 sm:p-6">
                @foreach (['admin_name'=>'Name','admin_email'=>'Email / Login','admin_password'=>'Password','admin_password_confirmation'=>'Confirm Password'] as $field => $label)
                    <div><label class="block text-sm font-medium text-slate-700">{{ $label }}</label><input type="{{ str_contains($field, 'password') ? 'password' : ($field === 'admin_email' ? 'email' : 'text') }}" wire:model="{{ $field }}" class="mt-2 block w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm">@error($field)<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                @endforeach
            </div>
        </div>

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4 sm:px-6"><h2 class="text-sm font-semibold text-slate-900">Status</h2></div>
            <div class="p-5 sm:p-6"><select wire:model="status" class="block w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm sm:max-w-md"><option value="">Select status</option>@foreach (TenantStatus::cases() as $tenantStatus)<option value="{{ $tenantStatus->value }}">{{ $tenantStatus->label() }}</option>@endforeach</select>@error('status')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror</div>
        </div>

        <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end"><button type="button" wire:click="cancel" class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700">Cancel</button><button type="submit" wire:loading.attr="disabled" class="rounded-xl bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white disabled:opacity-60"><span wire:loading.remove>Create Tenant & Administrator</span><span wire:loading>Creating...</span></button></div>
    </form>
</div>
