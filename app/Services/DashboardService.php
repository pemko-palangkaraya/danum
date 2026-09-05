<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\OutgoingLetterStatus;
use App\Enums\TenantStatus;
use App\Models\AuditLog;
use App\Models\OutgoingLetter;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class DashboardService
{
    public function summarize(User $user): array
    {
        $isSuperAdmin = $user->isSuperAdmin();
        $tenantId = $isSuperAdmin ? null : $user->tenant_id;
        $tenantName = $user->tenant?->name ?? 'Seluruh Tenant';

        $letters = OutgoingLetter::query()->when($tenantId, fn (Builder $query) => $query->where('tenant_id', $tenantId));
        $stats = $this->letterStats($letters, $user, $isSuperAdmin);
        $population = $user->hasPermission('population.view')
            ? app(PopulationStatisticsService::class)->summarize($tenantId)
            : null;

        return [
            'isSuperAdmin' => $isSuperAdmin,
            'canViewPopulation' => $population !== null,
            'tenantName' => $tenantName,
            'stats' => $stats,
            'controlCards' => $this->controlCards($stats, $isSuperAdmin),
            'population' => $population,
            'populationCards' => $this->populationCards($population),
            'workflow' => [
                ['label' => 'Draft', 'value' => $stats['drafts']],
                ['label' => 'Verifikasi', 'value' => $stats['submitted']],
                ['label' => 'Siap TTE', 'value' => $stats['validated']],
                ['label' => 'Terbit', 'value' => $stats['issued']],
            ],
            'recentLetters' => $this->recentLetters($letters),
            'activities' => $this->activities($tenantId),
            'tenantBreakdown' => $isSuperAdmin ? $this->tenantBreakdown() : collect(),
        ];
    }

    private function letterStats(Builder $letters, User $user, bool $isSuperAdmin): array
    {
        $drafts = (clone $letters)->where('status', OutgoingLetterStatus::DRAFT)->whereNull('submitted_at');
        $submitted = (clone $letters)->where('status', OutgoingLetterStatus::DRAFT)->whereNotNull('submitted_at');
        $validated = (clone $letters)->where('status', OutgoingLetterStatus::VALIDATED);
        $issued = (clone $letters)->where('status', OutgoingLetterStatus::ISSUED);

        $stats = [
            'letters' => (clone $letters)->count(),
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
            $stats['my_letters'] = (clone $letters)->where('created_by', $user->id)->count();
            $stats['my_submitted'] = (clone $letters)->where('created_by', $user->id)->where('status', OutgoingLetterStatus::DRAFT)->whereNotNull('submitted_at')->count();
            $stats['my_validated'] = (clone $letters)->where('created_by', $user->id)->where('status', OutgoingLetterStatus::VALIDATED)->count();
        }

        return $stats;
    }

    private function controlCards(array $stats, bool $isSuperAdmin): array
    {
        return $isSuperAdmin
            ? [
                ['label' => 'Organisasi', 'value' => $stats['tenants'], 'hint' => $stats['active_tenants'].' aktif'],
                ['label' => 'Pengguna', 'value' => $stats['users'], 'hint' => 'Seluruh platform'],
                ['label' => 'Surat Terbit', 'value' => $stats['issued'], 'hint' => $stats['active'].' masih aktif'],
                ['label' => 'Perlu Perhatian', 'value' => $stats['submitted'] + $stats['validated'], 'hint' => $stats['submitted'].' verifikasi · '.$stats['validated'].' siap TTE'],
            ]
            : [
                ['label' => 'Total Surat', 'value' => $stats['letters'], 'hint' => $stats['my_letters'].' dibuat Anda'],
                ['label' => 'Anggota', 'value' => $stats['users'], 'hint' => 'Dalam organisasi'],
                ['label' => 'Surat Aktif', 'value' => $stats['active'], 'hint' => $stats['issued'].' telah terbit'],
                ['label' => 'Perlu Tindakan', 'value' => $stats['submitted'] + $stats['validated'], 'hint' => $stats['submitted'].' verifikasi · '.$stats['validated'].' siap TTE'],
            ];
    }

    private function populationCards(?array $population): array
    {
        if ($population === null) {
            return [];
        }

        $total = (int) $population['totalCitizens'];
        $percent = static fn (int $value): string => $total > 0 ? number_format(($value / $total) * 100, 1).'%' : '0.0%';

        return [
            ['label' => 'Total penduduk', 'value' => $population['totalCitizens'], 'hint' => 'Warga terdata', 'tone' => 'indigo'],
            ['label' => 'Total KK', 'value' => $population['totalFamilies'], 'hint' => 'Kartu keluarga', 'tone' => 'violet'],
            ['label' => 'Laki-laki', 'value' => $population['male'], 'hint' => $percent((int) $population['male']).' dari penduduk', 'tone' => 'cyan'],
            ['label' => 'Perempuan', 'value' => $population['female'], 'hint' => $percent((int) $population['female']).' dari penduduk', 'tone' => 'pink'],
        ];
    }

    private function recentLetters(Builder $letters): Collection
    {
        return (clone $letters)->with(['creator', 'tenant'])->latest('updated_at')->limit(6)->get()->map(function (OutgoingLetter $letter): array {
            $status = $letter->status->value;
            $effectiveState = $status === 'issued' && $letter->isExpired() ? 'expired' : $status;
            $rejected = filled($letter->rejection_reason);

            return [
                'id' => $letter->id,
                'subject' => $letter->subject ?: 'Tanpa perihal',
                'number' => $letter->number ?: 'Nomor belum tersedia',
                'owner' => $letter->tenant?->name ?? $letter->creator?->name ?? 'Pengguna',
                'status' => $rejected ? 'Ditolak' : ($letter->submitted_at && $status === 'draft' ? 'Menunggu Verifikasi' : match ($effectiveState) {
                    'issued' => 'Terbit',
                    'validated' => 'Siap TTE',
                    'withdrawn' => 'Ditarik',
                    'expired' => 'Kedaluwarsa',
                    'cancelled' => 'Dibatalkan',
                    default => 'Draft',
                }),
                'statusClass' => $rejected || in_array($effectiveState, ['withdrawn', 'cancelled'], true)
                    ? 'bg-red-100 text-red-700'
                    : match ($effectiveState) {
                        'issued' => 'bg-emerald-100 text-emerald-700',
                        'validated', 'expired' => 'bg-amber-100 text-amber-800',
                        'draft' => $letter->submitted_at ? 'bg-blue-100 text-blue-800' : 'bg-slate-100 text-slate-600',
                        default => 'bg-slate-100 text-slate-600',
                    },
            ];
        });
    }

    private function activities(?string $tenantId): Collection
    {
        return AuditLog::query()
            ->with(['user', 'tenant'])
            ->when($tenantId, fn (Builder $query) => $query->where('tenant_id', $tenantId))
            ->latest('created_at')
            ->limit(6)
            ->get()
            ->map(fn (AuditLog $activity): array => [
                'action' => str_replace('_', ' ', ucfirst($activity->action)),
                'actor' => $activity->user?->name ?? 'Sistem',
                'when' => $activity->created_at?->diffForHumans(),
            ]);
    }

    private function tenantBreakdown(): Collection
    {
        return Tenant::query()
            ->withCount('users')
            ->orderByDesc('created_at')
            ->limit(6)
            ->get()
            ->map(fn (Tenant $tenant): array => [
                'name' => $tenant->name,
                'status' => $tenant->status->label(),
                'users' => $tenant->users_count,
                'issued' => OutgoingLetter::query()->where('tenant_id', $tenant->id)->where('status', OutgoingLetterStatus::ISSUED)->count(),
            ]);
    }
}
