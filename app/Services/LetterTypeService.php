<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\LetterType;
use App\Models\LetterTypeVersion;
use App\Repositories\Contracts\LetterTypeRepositoryInterface;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

class LetterTypeService
{
    public function __construct(
        private LetterTypeRepositoryInterface $repository,
        private DocxTemplateService $templateService,
    ) {}

    public function create(array $data): LetterType
    {
        return DB::transaction(function () use ($data): LetterType {
            $letterType = $this->repository->create($data);

            if (!empty($data['body_template']) || !empty($data['template_path'])) {
                $this->ensureCurrentVersion($letterType);
            }

            return $letterType;
        });
    }

    public function update(LetterType $letterType, array $data): LetterType
    {
        return DB::transaction(function () use ($letterType, $data): LetterType {
            $templateChanged = array_key_exists('body_template', $data) && $data['body_template'] !== $letterType->body_template;
            $pathChanged = array_key_exists('template_path', $data) && $data['template_path'] !== $letterType->template_path;

            if ($templateChanged && $data['body_template'] !== null) {
                $this->templateService->validate((string) $data['body_template']);
            }

            $letterType = $this->repository->update($letterType, $data);

            if ($templateChanged || $pathChanged) {
                $this->ensureCurrentVersion($letterType);
            }

            return $letterType;
        });
    }

    public function delete(LetterType $letterType): bool { return $this->repository->delete($letterType); }
    public function restore(LetterType $letterType): bool { return $this->repository->restore($letterType); }
    public function currentVersion(LetterType $letterType): ?LetterTypeVersion { return $this->activeVersion($letterType); }

    public function activeVersion(LetterType $letterType, ?Carbon $at = null): ?LetterTypeVersion
    {
        $at ??= now();
        return LetterTypeVersion::query()->where('letter_type_id', $letterType->id)->where('is_active', true)
            ->where(function ($q) use ($at): void { $q->whereNull('effective_from')->orWhere('effective_from', '<=', $at); })
            ->where(function ($q) use ($at): void { $q->whereNull('effective_until')->orWhere('effective_until', '>', $at); })
            ->orderByDesc('version')->first();
    }

    public function createVersion(LetterType $letterType, array $data, int $createdBy): LetterTypeVersion
    {
        if (! $letterType->isGlobal()) {
            throw new \DomainException('Template version hanya dapat dikelola untuk jenis surat global.');
        }

        return DB::transaction(function () use ($letterType, $data, $createdBy): LetterTypeVersion {
            $effectiveFrom = $this->parseDatePreservingPrecision($data['effective_from'] ?? now());
            $effectiveUntil = !empty($data['effective_until']) ? $this->parseDatePreservingPrecision($data['effective_until']) : null;
            $latest = LetterTypeVersion::query()->where('letter_type_id', $letterType->id)->orderByDesc('version')->first();

            if ($latest?->effective_from && $effectiveFrom->lte($latest->effective_from)) {
                throw new \DomainException('Versi baru harus memiliki periode mulai setelah versi terakhir.');
            }
            if ($latest?->effective_until && $effectiveFrom->lt($latest->effective_until)) {
                throw new \DomainException('Periode versi baru tidak boleh bertumpang tindih dengan versi sebelumnya.');
            }
            if ($effectiveUntil !== null && $effectiveUntil->lte($effectiveFrom)) {
                throw new \DomainException('Tanggal selesai harus lebih besar dari tanggal mulai.');
            }

            $variables = array_values(array_unique(array_filter(array_map(
                static fn ($value) => trim((string) $value),
                array_key_exists('variables', $data) ? ($data['variables'] ?? []) : ($letterType->variables ?? [])
            ))));
            $currentVariables = array_values(array_unique(array_filter(array_map(
                static fn ($value) => trim((string) $value),
                $letterType->variables ?? []
            ))));
            $missingFromVersion = array_values(array_diff($currentVariables, $variables));
            if ($missingFromVersion) {
                throw new \DomainException('Versi baru tidak boleh menghapus variabel yang sudah tersedia pada jenis surat: '.implode(', ', $missingFromVersion).'.');
            }

            if ($latest !== null && $latest->effective_until === null) {
                $latest->update(['effective_until' => $effectiveFrom]);
            }

            $version = LetterTypeVersion::query()->create([
                'letter_type_id' => $letterType->id,
                'version' => ($latest?->version ?? 0) + 1,
                'body_template' => (string) ($data['body_template'] ?? $letterType->body_template ?? ''),
                'template_path' => $data['template_path'] ?? $letterType->template_path,
                'variables' => $variables,
                'effective_from' => $effectiveFrom,
                'effective_until' => $effectiveUntil,
                'is_active' => true,
                'change_note' => trim((string) ($data['change_note'] ?? '')) ?: null,
                'created_by' => $createdBy,
            ]);

            app(AuditLogService::class)->record(
                'letter_type.version.created',
                $version,
                null,
                [
                    'letter_type_id' => $version->letter_type_id,
                    'version' => $version->version,
                    'template_path' => $version->template_path,
                    'variables' => $version->variables,
                    'effective_from' => $version->effective_from?->toIso8601String(),
                    'effective_until' => $version->effective_until?->toIso8601String(),
                    'change_note' => $version->change_note,
                ],
                $createdBy,
            );

            return $version;
        });
    }

    public function ensureCurrentVersion(LetterType $letterType): ?LetterTypeVersion
    {
        $template = $letterType->body_template;
        $path = $letterType->template_path;
        if ($template === null && $path === null) return null;

        $latest = LetterTypeVersion::query()->where('letter_type_id', $letterType->id)->orderByDesc('version')->first();
        if ($latest && $latest->body_template === (string) ($template ?? '') && $latest->template_path === $path) {
            return $this->activeVersion($letterType) ?? $latest;
        }

        return $this->createVersion($letterType, [
            'body_template' => $template,
            'template_path' => $path,
            'effective_from' => now(),
            'change_note' => 'Sinkronisasi template master.',
            'variables' => $letterType->variables ?? [],
        ], auth()->id() ?? 0);
    }

    private function parseDatePreservingPrecision(mixed $value): Carbon
    {
        return $value instanceof CarbonInterface
            ? $value->copy()
            : Carbon::parse($value);
    }
}
