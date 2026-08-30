<?php

declare(strict_types=1);

use App\Enums\OutgoingLetterStatus;
use App\Enums\TenantStatus;
use App\Models\AuditLog;
use App\Models\OutgoingLetter;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component {
    public function with(): array
    {
        $user = auth()->user();
        $isSuperAdmin = $user->isSuperAdmin();

        $letters = OutgoingLetter::query();
        if (! $isSuperAdmin) {
            $letters->where('tenant_id', $user->tenant_id);
        }

        $base = clone $letters;
        $drafts = (clone $base)->where('status', OutgoingLetterStatus::DRAFT)->whereNull('submitted_at');
        $submitted = (clone $base)->where('status', OutgoingLetterStatus::DRAFT)->whereNotNull('submitted_at');
        $validated = (clone $base)->where('status', OutgoingLetterStatus::VALIDATED);
        $issued = (clone $base)->where('status', OutgoingLetterStatus::ISSUED);

        $stats = [
            'letters' => $base->count(),
            'drafts' => $drafts->count(),
            'submitted' => $submitted->count(),
            'validated' => $validated->count(),
            'issued' => $issued->count(),
            'active' => (clone $issued)->where(function (Builder $query): void {
                $query->whereNull('valid_from')->orWhere('valid_from', '<=', now());
            })->where(function (Builder $query): void {
                $query->whereNull('valid_until')->orWhere('valid_until', '>', now());
            })->count(),
        ];

        if ($isSuperAdmin) {
            $stats['tenants'] = Tenant::query()->count();
            $stats['active_tenants'] = Tenant::query()->where('status', TenantStatus::ACTIVE)->count();
            $stats['users'] = User::query()->count();
        } else {
            $stats['users'] = User::query()->where('tenant_id', $user->tenant_id)->count();
            $stats['my_letters'] = (clone $base)->where('created_by', $user->id)->count();
            $stats['my_submitted'] = (clone $base)->where('created_by', $user->id)->where('status', OutgoingLetterStatus::DRAFT)->whereNotNull('submitted_at')->count();
            $stats['my_validated'] = (clone $base)->where('created_by', $user->id)->where('status', OutgoingLetterStatus::VALIDATED)->count();
        }

        $recentLetters = (clone $base)->with(['creator', 'tenant'])->latest('updated_at')->limit(6)->get();

        $activityQuery = AuditLog::query()->with(['user', 'tenant'])->latest('created_at');
        if (! $isSuperAdmin) {
            $activityQuery->where('tenant_id', $user->tenant_id);
        }
        $activities = $activityQuery->limit(6)->get();

        $tenantBreakdown = collect();
        if ($isSuperAdmin) {
            $tenantBreakdown = Tenant::query()
                ->withCount(['users'])
                ->orderByDesc('created_at')
                ->limit(6)
                ->get()
                ->map(function (Tenant $tenant): array {
                    $letters = OutgoingLetter::query()->where('tenant_id', $tenant->id);
                    return [
                        'name' => $tenant->name,
                        'status' => $tenant->status->label(),
                        'users' => $tenant->users_count,
                        'issued' => (clone $letters)->where('status', OutgoingLetterStatus::ISSUED)->count(),
                    ];
                });
        }

        return compact('isSuperAdmin', 'stats', 'recentLetters', 'activities', 'tenantBreakdown');
    }
};
?>

<div>
    @php
        $user = auth()->user();
        $tenant = $user->tenant;
        $tenantName = $tenant?->name ?? 'Seluruh Tenant';
    @endphp

    <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-sm font-medium text-slate-400">{{ $isSuperAdmin ? 'Platform Administration' : 'Workspace' }}</p>
            <h1 class="mt-1 text-3xl font-semibold tracking-tight text-slate-900">Dashboard</h1>
            <p class="mt-2 text-sm text-slate-500">Selamat datang, {{ $user->name }}. Ringkasan ini diperbarui dari kondisi {{ $isSuperAdmin ? 'platform DANUM' : 'organisasi '.$tenantName }}.</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
            <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">{{ $isSuperAdmin ? 'Scope' : 'Organisasi' }}</p>
            <p class="mt-1 text-sm font-semibold text-slate-800">{{ $tenantName }}</p>
        </div>
    </div>

    <div class="overflow-hidden rounded-3xl bg-slate-900 shadow-xl">
        <div class="p-6 sm:p-8">
            <div class="flex flex-col gap-7 lg:flex-row lg:items-center lg:justify-between">
                <div class="max-w-2xl">
                    <div class="inline-flex items-center gap-2 rounded-full bg-white/10 px-3 py-1 text-xs font-semibold text-slate-200"><span class="h-1.5 w-1.5 rounded-full bg-emerald-400"></span>Data live</div>
                    <h2 class="mt-4 text-2xl font-semibold tracking-tight text-white sm:text-3xl">{{ $isSuperAdmin ? 'Pusat kendali platform DANUM.' : 'Pusat kendali '.$tenantName.'.' }}</h2>
                    <p class="mt-3 text-sm leading-6 text-slate-300">{{ $isSuperAdmin ? 'Pantau organisasi, pengguna, dan seluruh alur surat dari satu dashboard.' : 'Pantau surat, pekerjaan workflow, anggota, dan aktivitas organisasi Anda.' }}</p>
                </div>

                @php
                    $controlCards = $isSuperAdmin ? [
                        ['label'=>'Organisasi','value'=>$stats['tenants'],'hint'=>$stats['active_tenants'].' aktif'],
                        ['label'=>'Pengguna','value'=>$stats['users'],'hint'=>'Seluruh platform'],
                        ['label'=>'Surat Terbit','value'=>$stats['issued'],'hint'=>$stats['active'].' masih aktif'],
                        ['label'=>'Perlu Perhatian','value'=>$stats['submitted'] + $stats['validated'],'hint'=>$stats['submitted'].' verifikasi · '.$stats['validated'].' siap TTE'],
                    ] : [
                        ['label'=>'Total Surat','value'=>$stats['letters'],'hint'=>$stats['my_letters'].' dibuat Anda'],
                        ['label'=>'Anggota','value'=>$stats['users'],'hint'=>'Dalam organisasi'],
                        ['label'=>'Surat Aktif','value'=>$stats['active'],'hint'=>$stats['issued'].' telah terbit'],
                        ['label'=>'Perlu Tindakan','value'=>$stats['submitted'] + $stats['validated'],'hint'=>$stats['submitted'].' verifikasi · '.$stats['validated'].' siap TTE'],
                    ];
                @endphp

                <div class="grid grid-cols-2 gap-3 sm:grid-cols-4 lg:w-[500px] lg:shrink-0">
                    @foreach($controlCards as $card)
                        <div class="rounded-2xl bg-white/10 p-4 backdrop-blur-sm">
                            <p class="text-2xl font-semibold text-white">{{ number_format($card['value']) }}</p>
                            <p class="mt-1 text-[11px] font-medium text-slate-300">{{ $card['label'] }}</p>
                            <p class="mt-2 text-[10px] leading-4 text-slate-500">{{ $card['hint'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <section class="mt-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-semibold text-slate-900">Status workflow</h2>
                <p class="mt-1 text-xs text-slate-500">Status surat bersifat berurutan: Draft → Verifikasi → Siap TTE → Terbit.</p>
            </div>
            <span class="rounded-full bg-emerald-50 px-3 py-1 text-[11px] font-semibold text-emerald-700">Live</span>
        </div>
        <div class="mt-6 grid gap-3 sm:grid-cols-4">
            @foreach ([['label'=>'Draft','value'=>$stats['drafts']],['label'=>'Verifikasi','value'=>$stats['submitted']],['label'=>'Siap TTE','value'=>$stats['validated']],['label'=>'Terbit','value'=>$stats['issued']]] as $step)
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <p class="text-xs font-medium text-slate-500">{{ $step['label'] }}</p>
                    <p class="mt-2 text-2xl font-semibold text-slate-900">{{ number_format($step['value']) }}</p>
                </div>
            @endforeach
        </div>
        @if($stats['letters'] > 0)
            <div class="mt-5 flex h-2 overflow-hidden rounded-full bg-slate-100">
                @foreach ([['value'=>$stats['drafts']],['value'=>$stats['submitted']],['value'=>$stats['validated']],['value'=>$stats['issued']]] as $segment)
                    <span class="h-full bg-slate-900" style="width: {{ min(100, ($segment['value'] / $stats['letters']) * 100) }}%"></span>
                @endforeach
            </div>
        @endif
    </section>

    @if($isSuperAdmin)
        <section class="mt-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex items-center justify-between"><div><h2 class="font-semibold text-slate-900">Kondisi organisasi</h2><p class="mt-1 text-xs text-slate-500">Ringkasan tenant terbaru di platform.</p></div>@if(Route::has('tenants.index'))<a href="{{ route('tenants.index') }}" class="text-xs font-semibold text-slate-700 hover:text-slate-900">Kelola →</a>@endif</div>
            <div class="mt-5 overflow-x-auto"><table class="min-w-full text-left text-sm"><thead class="border-b border-slate-200 text-xs text-slate-400"><tr><th class="pb-3 pr-4 font-medium">Organisasi</th><th class="pb-3 pr-4 font-medium">Status</th><th class="pb-3 pr-4 font-medium">Pengguna</th><th class="pb-3 font-medium">Surat Terbit</th></tr></thead><tbody class="divide-y divide-slate-100">
                @forelse($tenantBreakdown as $row)
                    <tr><td class="py-3 pr-4 font-medium text-slate-800">{{ $row['name'] }}</td><td class="py-3 pr-4 text-slate-500">{{ $row['status'] }}</td><td class="py-3 pr-4 text-slate-600">{{ number_format($row['users']) }}</td><td class="py-3 text-slate-600">{{ number_format($row['issued']) }}</td></tr>
                @empty
                    <tr><td colspan="4" class="py-8 text-center text-sm text-slate-400">Belum ada organisasi.</td></tr>
                @endforelse
            </tbody></table></div>
        </section>
    @endif

    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex items-center justify-between"><div><h2 class="font-semibold text-slate-900">Surat terbaru</h2><p class="mt-1 text-xs text-slate-500">{{ $isSuperAdmin ? 'Aktivitas surat seluruh platform.' : 'Aktivitas surat dalam organisasi ini.' }}</p></div>@can('viewAny', App\Models\OutgoingLetter::class)<a href="{{ route('outgoing-letters.index') }}" class="text-xs font-semibold text-slate-700">Lihat semua →</a>@endcan</div>
            <div class="mt-5 space-y-2">
                @forelse($recentLetters as $letter)
                    @php
                        $submitted = $letter->submitted_at !== null;
                        $rejected = filled($letter->rejection_reason);
                        $statusKey = $letter->status->value;
                        $effectiveState = $statusKey === 'issued' && $letter->isExpired() ? 'expired' : $statusKey;
                        $statusLabel = $submitted && $statusKey === 'draft' ? 'Menunggu Verifikasi' : ($rejected ? 'Ditolak' : match ($effectiveState) {
                            'withdrawn' => 'Ditarik',
                            'expired' => 'Kedaluwarsa',
                            'issued' => 'Issued',
                            'validated' => 'Validated',
                            'cancelled' => 'Cancelled',
                            default => 'Draft',
                        });
                        $statusClass = match ($effectiveState) {
                            'issued' => 'bg-emerald-100 text-emerald-700',
                            'validated', 'expired' => 'bg-amber-100 text-amber-800',
                            'withdrawn', 'cancelled' => 'bg-red-100 text-red-700',
                            'draft' => $submitted ? 'bg-blue-100 text-blue-800' : 'bg-slate-100 text-slate-600',
                            default => 'bg-slate-100 text-slate-600',
                        };
                        if ($rejected) {
                            $statusClass = 'bg-red-100 text-red-700';
                        }
                    @endphp
                    <a href="{{ route('outgoing-letters.show', $letter->id) }}" class="flex items-center justify-between gap-4 rounded-xl border border-slate-100 px-4 py-3 hover:bg-slate-50">
                        <div class="min-w-0"><p class="truncate text-sm font-medium text-slate-800">{{ $letter->subject ?: 'Tanpa perihal' }}</p><p class="mt-1 truncate text-xs text-slate-400">{{ $letter->number ?: 'Nomor belum tersedia' }} · {{ $isSuperAdmin ? ($letter->tenant?->name ?? 'Tanpa organisasi') : ($letter->creator?->name ?? 'Pengguna') }}</p></div>
                        <span class="shrink-0 rounded-full px-2.5 py-1 text-[10px] font-semibold uppercase {{ $statusClass }}">{{ $statusLabel }}</span>
                    </a>
                @empty
                    <div class="rounded-xl border border-dashed border-slate-200 p-8 text-center text-sm text-slate-400">Belum ada surat.</div>
                @endforelse
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div><h2 class="font-semibold text-slate-900">Aktivitas terbaru</h2><p class="mt-1 text-xs text-slate-500">Audit log sesuai scope akun.</p></div>
            <div class="mt-5 space-y-2">
                @forelse($activities as $activity)
                    <div class="flex gap-3 rounded-xl border border-slate-100 px-4 py-3"><div class="mt-1 h-2 w-2 shrink-0 rounded-full bg-slate-400"></div><div class="min-w-0"><p class="text-sm font-medium text-slate-700">{{ str_replace('_', ' ', ucfirst($activity->action)) }}</p><p class="mt-1 text-xs text-slate-400">{{ $activity->user?->name ?? 'Sistem' }} · {{ $activity->created_at?->diffForHumans() }}</p></div></div>
                @empty
                    <div class="rounded-xl border border-dashed border-slate-200 p-8 text-center text-sm text-slate-400">Belum ada aktivitas.</div>
                @endforelse
            </div>
        </section>
    </div>
</div>
