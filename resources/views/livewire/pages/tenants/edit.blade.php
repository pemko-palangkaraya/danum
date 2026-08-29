<?php

use App\Enums\TenantStatus;
use App\Enums\UserStatus;
use App\Http\Requests\UpdateTenantRequest;
use App\Services\TenantService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;

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
    public ?string $currentLetterhead = null;

    public string $administratorName = '';
    public string $administratorEmail = '';
    public string $administratorPassword = '';
    public string $administratorPasswordConfirmation = '';
    public string $administratorStatus = '';
    public ?int $administratorId = null;

    public string $tenantId = '';

    public function mount(TenantService $tenantService): void
    {
        $this->tenantId = (string) request()->route('tenant');

        $tenant = $tenantService->find($this->tenantId);
        abort_unless($tenant, 404);

        $this->code = $tenant->code ?? '';
        $this->name = $tenant->name ?? '';
        $this->tenant_category_id = (string) ($tenant->tenant_category_id ?? '');
        $this->province = $tenant->province ?? '';
        $this->city = $tenant->city ?? '';
        $this->district = $tenant->district ?? '';
        $this->village = $tenant->village ?? '';
        $this->address = $tenant->address ?? '';
        $this->phone = $tenant->phone ?? '';
        $this->email = $tenant->email ?? '';
        $this->logo = $tenant->logo ?? '';
        $this->head_name = $tenant->head_name ?? '';
        $this->head_title = $tenant->head_title ?? '';
        $this->status = (string) ($tenant->status?->value ?? $tenant->status ?? '');
        $this->currentLetterhead = $tenant->letterheadUrl();

        $administrator = $tenant->administrator;
        if ($administrator) {
            $this->administratorId = $administrator->id;
            $this->administratorName = $administrator->name;
            $this->administratorEmail = $administrator->email;
            $this->administratorStatus = $administrator->status?->value ?? (string) $administrator->status;
        }
    }

    public function save(TenantService $tenantService): void
    {
        $tenant = $tenantService->find($this->tenantId);
        abort_unless($tenant, 404);
        $this->authorize('update', $tenant);

        $data = [
            'code' => $this->code,
            'name' => $this->name,
            'tenant_category_id' => $this->tenant_category_id,
            'province' => $this->province,
            'city' => $this->city,
            'district' => $this->district,
            'village' => $this->village,
            'address' => $this->address ?: null,
            'phone' => $this->phone ?: null,
            'email' => $this->email ?: null,
            'logo' => $this->logo ?: null,
            'head_name' => $this->head_name ?: null,
            'head_title' => $this->head_title ?: null,
            'status' => $this->status,
        ];

        $rules = (new UpdateTenantRequest())->rules();
        $rules['code'] = [
            'sometimes', 'required', 'string', 'max:50',
            Rule::unique('tenants', 'code')->ignore($tenant->id),
        ];

        $validated = Validator::make($data, $rules)->validate();

        if ($this->letterhead) {
            $this->validate([
                'letterhead' => ['file', 'image', 'mimes:png,jpg,jpeg,webp', 'max:4096'],
            ]);

            $oldLetterhead = $tenant->letterhead_path;
            $newLetterhead = $this->letterhead->store('tenant-letterheads', 'public');
            $validated['letterhead_path'] = $newLetterhead;

            if ($oldLetterhead && $oldLetterhead !== $newLetterhead) {
                Storage::disk('public')->delete($oldLetterhead);
            }
        }

        if ($this->administratorId !== null) {
            $adminRules = [
                'name' => ['required', 'string', 'max:255'],
                'email' => [
                    'required', 'email', 'max:255',
                    Rule::unique('users', 'email')->ignore($this->administratorId),
                ],
                'password' => ['nullable', 'string', 'min:8'],
                'password_confirmation' => ['same:password'],
                'status' => [Rule::enum(UserStatus::class)],
            ];

            $administrator = Validator::make([
                'name' => $this->administratorName,
                'email' => $this->administratorEmail,
                'password' => $this->administratorPassword ?: null,
                'password_confirmation' => $this->administratorPasswordConfirmation,
                'status' => $this->administratorStatus,
            ], $adminRules)->validate();

            $validated['_administrator'] = $administrator;
        }

        $tenantService->update($tenant, $validated);

        $this->redirect(route('tenants.index'));
    }

    public function cancel(): void
    {
        $this->redirect(route('tenants.index'));
    }
};
?>

<div class="space-y-6">
    <div class="flex items-center gap-3">
        <a href="{{ route('tenants.show', $this->tenantId) }}" class="rounded-lg p-1.5 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700" aria-label="Back to tenant">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-5 w-5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 18l-6-6 6-6" /></svg>
        </a>
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-slate-900">Edit Tenant</h1>
            <p class="mt-1 text-sm text-slate-500">Perbarui informasi tenant dan administrator awal.</p>
        </div>
    </div>

    <form wire:submit="save" class="space-y-6">
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4 sm:px-6"><h2 class="text-sm font-semibold text-slate-900">Basic Information</h2><p class="mt-1 text-xs text-slate-500">Informasi dasar organisasi.</p></div>
            <div class="grid gap-5 p-5 sm:grid-cols-2 sm:p-6">
                <div><label for="code" class="block text-sm font-medium text-slate-700">Code</label><input id="code" type="text" wire:model="code" maxlength="50" class="mt-2 block w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm outline-none focus:border-slate-400 focus:ring-2 focus:ring-slate-100">@error('code')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                <div><label for="name" class="block text-sm font-medium text-slate-700">Name</label><input id="name" type="text" wire:model="name" maxlength="150" class="mt-2 block w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm outline-none focus:border-slate-400 focus:ring-2 focus:ring-slate-100">@error('name')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                <div><label for="tenant_category_id" class="block text-sm font-medium text-slate-700">Kategori Organisasi</label><select id="tenant_category_id" wire:model="tenant_category_id" class="mt-2 block w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm"><option value="">Pilih kategori</option>@foreach (TenantCategory::query()->where('is_active', true)->orderBy('sort_order')->get() as $category)<option value="{{ $category->id }}">{{ $category->name }}</option>@endforeach</select>@error('tenant_category_id')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror</div>
            </div>
        </div>

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4 sm:px-6"><h2 class="text-sm font-semibold text-slate-900">Location</h2><p class="mt-1 text-xs text-slate-500">Lokasi administratif organisasi.</p></div>
            <div class="grid gap-5 p-5 sm:grid-cols-2 lg:grid-cols-4 sm:p-6">
                @foreach (['province'=>'Province','city'=>'City','district'=>'District','village'=>'Village'] as $field => $label)
                    <div><label for="{{ $field }}" class="block text-sm font-medium text-slate-700">{{ $label }}</label><input id="{{ $field }}" type="text" wire:model="{{ $field }}" maxlength="100" class="mt-2 block w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm outline-none focus:border-slate-400 focus:ring-2 focus:ring-slate-100">@error($field)<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                @endforeach
                <div class="sm:col-span-2 lg:col-span-4"><label for="address" class="block text-sm font-medium text-slate-700">Address</label><textarea id="address" wire:model="address" rows="3" class="mt-2 block w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm outline-none focus:border-slate-400 focus:ring-2 focus:ring-slate-100"></textarea>@error('address')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror</div>
            </div>
        </div>

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4 sm:px-6"><h2 class="text-sm font-semibold text-slate-900">Contact & Leadership</h2><p class="mt-1 text-xs text-slate-500">Informasi kontak dan pimpinan organisasi.</p></div>
            <div class="grid gap-5 p-5 sm:grid-cols-2 sm:p-6">
                @foreach (['phone'=>['label'=>'Phone','type'=>'text','maxlength'=>30],'email'=>['label'=>'Email','type'=>'email','maxlength'=>150],'head_name'=>['label'=>'Head Name','type'=>'text','maxlength'=>150],'head_title'=>['label'=>'Head Title','type'=>'text','maxlength'=>100]] as $field => $config)
                    <div><label for="{{ $field }}" class="block text-sm font-medium text-slate-700">{{ $config['label'] }}</label><input id="{{ $field }}" type="{{ $config['type'] }}" wire:model="{{ $field }}" maxlength="{{ $config['maxlength'] }}" class="mt-2 block w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm outline-none focus:border-slate-400 focus:ring-2 focus:ring-slate-100">@error($field)<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                @endforeach
            </div>
        </div>

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4 sm:px-6"><h2 class="text-sm font-semibold text-slate-900">Letterhead / Kop Surat</h2><p class="mt-1 text-xs text-slate-500">Kop ini akan digunakan oleh tenant pada surat yang diterbitkan.</p></div>
            <div class="grid gap-5 p-5 sm:grid-cols-2 sm:p-6">
                <div>
                    <label for="letterhead" class="block text-sm font-medium text-slate-700">Upload Kop Surat</label>
                    <input id="letterhead" type="file" wire:model="letterhead" accept="image/png,image/jpeg,image/webp" class="mt-2 block w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm">
                    <p class="mt-1.5 text-xs text-slate-500">PNG, JPG/JPEG, atau WEBP. Maksimal 4 MB.</p>
                    @error('letterhead')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
                    <div wire:loading wire:target="letterhead" class="mt-2 text-xs text-slate-500">Uploading...</div>
                </div>
                <div>
                    <p class="text-sm font-medium text-slate-700">Preview Kop Aktif</p>
                    <div class="mt-2 flex min-h-32 items-center justify-center rounded-xl border border-dashed border-slate-200 bg-slate-50 p-4">
                        @if ($letterhead)
                            <img src="{{ $letterhead->temporaryUrl() }}" alt="Preview kop surat baru" class="max-h-40 max-w-full object-contain">
                        @elseif ($currentLetterhead)
                            <img src="{{ $currentLetterhead }}" alt="Kop surat tenant" class="max-h-40 max-w-full object-contain">
                        @else
                            <span class="text-xs text-slate-400">Belum ada kop surat.</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4 sm:px-6"><h2 class="text-sm font-semibold text-slate-900">Initial Administrator</h2><p class="mt-1 text-xs text-slate-500">Perbarui akun administrator tenant yang dibuat saat tenant pertama kali dibuat.</p></div>
            <div class="grid gap-5 p-5 sm:grid-cols-2 sm:p-6">
                @if ($administratorId)
                    <div><label for="administratorName" class="block text-sm font-medium text-slate-700">Name</label><input id="administratorName" type="text" wire:model="administratorName" maxlength="255" class="mt-2 block w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm outline-none focus:border-slate-400 focus:ring-2 focus:ring-slate-100">@error('administratorName')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                    <div><label for="administratorEmail" class="block text-sm font-medium text-slate-700">Email / Login</label><input id="administratorEmail" type="email" wire:model="administratorEmail" maxlength="255" class="mt-2 block w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm outline-none focus:border-slate-400 focus:ring-2 focus:ring-slate-100">@error('administratorEmail')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                    <div><label for="administratorPassword" class="block text-sm font-medium text-slate-700">New Password <span class="font-normal text-slate-400">(optional)</span></label><input id="administratorPassword" type="password" wire:model="administratorPassword" autocomplete="new-password" class="mt-2 block w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm outline-none focus:border-slate-400 focus:ring-2 focus:ring-slate-100">@error('administrator.password')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                    <div><label for="administratorPasswordConfirmation" class="block text-sm font-medium text-slate-700">Confirm Password</label><input id="administratorPasswordConfirmation" type="password" wire:model="administratorPasswordConfirmation" autocomplete="new-password" class="mt-2 block w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm outline-none focus:border-slate-400 focus:ring-2 focus:ring-slate-100">@error('administrator.password_confirmation')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                    <div><label for="administratorStatus" class="block text-sm font-medium text-slate-700">Status</label><select id="administratorStatus" wire:model="administratorStatus" class="mt-2 block w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm outline-none focus:border-slate-400 focus:ring-2 focus:ring-slate-100">@foreach (UserStatus::cases() as $userStatus)<option value="{{ $userStatus->value }}">{{ ucfirst($userStatus->value) }}</option>@endforeach</select>@error('administrator.status')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                @else
                    <div class="sm:col-span-2 rounded-xl border border-amber-100 bg-amber-50 px-4 py-3 text-sm text-amber-800">Tenant ini belum memiliki administrator yang tercatat. Buat administrator melalui menu <strong>Manage Users</strong>.</div>
                @endif
            </div>
        </div>

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4 sm:px-6"><h2 class="text-sm font-semibold text-slate-900">Status</h2></div>
            <div class="p-5 sm:p-6"><label for="status" class="block text-sm font-medium text-slate-700">Tenant Status</label><select id="status" wire:model="status" class="mt-2 block w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm outline-none focus:border-slate-400 focus:ring-2 focus:ring-slate-100 sm:max-w-md"><option value="">Select status</option>@foreach (TenantStatus::cases() as $tenantStatus)<option value="{{ $tenantStatus->value }}">{{ $tenantStatus->label() }}</option>@endforeach</select>@error('status')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror</div>
        </div>

        <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end"><button type="button" wire:click="cancel" class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">Cancel</button><button type="submit" wire:loading.attr="disabled" class="inline-flex items-center justify-center rounded-xl bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-60"><span wire:loading.remove>Save Changes</span><span wire:loading>Saving...</span></button></div>
    </form>
</div>
