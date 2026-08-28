<?php

use App\Enums\Permission;
use App\Enums\UserRole;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component {
    public function mount(): void
    {
        abort_unless(auth()->user()?->hasAnyPermission([Permission::RBAC_VIEW]), 403);
    }

    /** @return list<UserRole> */
    public function visibleRoles(): array
    {
        return auth()->user()?->isSuperAdmin()
            ? UserRole::cases()
            : [UserRole::TENANT_ADMIN];
    }

    /** @return list<Permission> */
    public function permissionsFor(UserRole $role): array
    {
        return Permission::forRole($role);
    }

    public function label(Permission $permission): string
    {
        return str($permission->value)->replace(['.', '-'], ' ')->title()->toString();
    }

    public function scope(UserRole $role): string
    {
        return $role === UserRole::SUPER_ADMIN ? 'Global / seluruh tenant' : 'Tenant sendiri';
    }
};
?>

<div class="space-y-6">
    <div>
        <p class="text-sm text-slate-500">Administration</p>
        <div class="mt-1 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight text-slate-900">Role &amp; Access Control</h1>
                <p class="mt-1 max-w-3xl text-sm text-slate-500">Lihat role dan permission efektif yang berlaku pada akun. Halaman ini bersifat terkontrol: akses global melihat seluruh matriks, sedangkan Tenant Admin hanya melihat batas akses role tenant-nya.</p>
            </div>
            <span class="inline-flex w-fit rounded-full bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-700">Role: {{ auth()->user()->role->value }}</span>
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-3">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Scope</p><p class="mt-2 text-sm font-semibold text-slate-900">{{ auth()->user()->isSuperAdmin() ? 'Global' : 'Tenant scoped' }}</p><p class="mt-1 text-xs text-slate-500">{{ auth()->user()->isSuperAdmin() ? 'Dapat mengawasi seluruh organisasi.' : 'Tidak dapat melampaui tenant sendiri.' }}</p></div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Active Permissions</p><p class="mt-2 text-2xl font-semibold text-slate-900">{{ count($this->permissionsFor(auth()->user()->role)) }}</p><p class="mt-1 text-xs text-slate-500">Permission efektif untuk role aktif.</p></div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Security Rule</p><p class="mt-2 text-sm font-semibold text-slate-900">Permission + Policy</p><p class="mt-1 text-xs text-slate-500">Permission tidak melewati tenant isolation atau object policy.</p></div>
    </div>

    <div class="space-y-4">
        @foreach ($this->visibleRoles() as $role)
            @php($permissions = $this->permissionsFor($role))
            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 bg-slate-50 px-5 py-4 sm:px-6">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <div><h2 class="text-sm font-semibold text-slate-900">{{ str($role->value)->replace('_', ' ')->title() }}</h2><p class="mt-0.5 text-xs text-slate-500">Scope: {{ $this->scope($role) }}</p></div>
                        <span class="w-fit rounded-full bg-white px-2.5 py-1 text-xs font-semibold text-slate-600 ring-1 ring-inset ring-slate-200">{{ count($permissions) }} permissions</span>
                    </div>
                </div>
                <div class="grid gap-px bg-slate-100 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach (Permission::cases() as $permission)
                        <div class="flex items-center justify-between gap-3 bg-white px-5 py-3">
                            <div><p class="text-sm font-medium text-slate-800">{{ $this->label($permission) }}</p><p class="text-[11px] text-slate-400">{{ $permission->value }}</p></div>
                            @if (in_array($permission, $permissions, true))
                                <span class="shrink-0 rounded-full bg-emerald-50 px-2 py-1 text-[11px] font-semibold text-emerald-700">Allowed</span>
                            @else
                                <span class="shrink-0 rounded-full bg-slate-100 px-2 py-1 text-[11px] font-semibold text-slate-500">Denied</span>
                            @endif
                        </div>
                    @endforeach
                </div>
            </section>
        @endforeach
    </div>

    <div class="rounded-2xl border border-amber-200 bg-amber-50 p-5 text-sm text-amber-900">
        <p class="font-semibold">Batasan akses</p>
        <ul class="mt-2 list-disc space-y-1 pl-5 text-xs leading-5 text-amber-800">
            <li>Akses global dapat melihat matriks semua role.</li>
            <li>Tenant Admin hanya dapat melihat konfigurasi role tenant dan tidak dapat memberi dirinya sendiri permission baru.</li>
            <li>Tenant User tidak memiliki permission <code class="font-mono">rbac.view</code>, sehingga menu dan halaman ini tidak tersedia.</li>
            <li>Halaman ini belum mengubah permission secara dinamis; matriks role tetap dikendalikan oleh kode agar tidak ada privilege escalation melalui UI.</li>
        </ul>
    </div>
</div>
