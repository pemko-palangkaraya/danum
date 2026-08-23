<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\LetterType;
use App\Models\LetterTypeVersion;
use App\Repositories\Contracts\LetterTypeRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class LetterTypeService
{
    public function __construct(
        private readonly LetterTypeRepositoryInterface $repository,
        private readonly OutgoingLetterTemplateService $templateService,
    ) {}

    public function find(string $id, ?string $tenantId): ?LetterType
    {
        return $this->repository->find($id, $tenantId);
    }

    public function findWithTrashed(string $id, ?string $tenantId): ?LetterType
    {
        return $this->repository->findWithTrashed($id, $tenantId);
    }

    public function getAll(?string $tenantId): Collection
    {
        return $this->repository->getAll($tenantId);
    }

    public function create(array $data): LetterType
    {
        return DB::transaction(function () use ($data): LetterType {
            if (($data['body_template'] ?? null) !== null) {
                $this->templateService->validate((string) $data['body_template']);
            }

            $letterType = $this->repository->create($data);
            $this->ensureCurrentVersion($letterType);

            return $letterType->refresh();
        });
    }

    public function update(LetterType $letterType, array $data): LetterType
    {
        return DB::transaction(function () use ($letterType, $data): LetterType {
            if (array_key_exists('body_template', $data)) {
                $template = $data['body_template'];

                if ($template !== null) {
                    $this->templateService->validate((string) $template);
                }

                if ($template !== $letterType->body_template) {
                    $letterType = $this->repository->update($letterType, $data);
                    $this->ensureCurrentVersion($letterType);

                    return $letterType;
                }
            }

            return $this->repository->update($letterType, $data);
        });
    }

    public function delete(LetterType $letterType): bool
    {
        return $this->repository->delete($letterType);
    }

    public function restore(LetterType $letterType): bool
    {
        return $this->repository->restore($letterType);
    }

    public function currentVersion(LetterType $letterType): ?LetterTypeVersion
    {
        return $letterType->currentVersion();
    }

    public function ensureCurrentVersion(LetterType $letterType): ?LetterTypeVersion
    {
        if ($letterType->body_template === null) {
            return null;
        }

        $latest = LetterTypeVersion::query()
            ->where('letter_type_id', $letterType->id)
            ->orderByDesc('version')
            ->first();

        if ($latest !== null && $latest->body_template === $letterType->body_template) {
            return $latest;
        }

        return LetterTypeVersion::query()->create([
            'letter_type_id' => $letterType->id,
            'version' => ($latest?->version ?? 0) + 1,
            'body_template' => $letterType->body_template,
        ]);
    }
}
