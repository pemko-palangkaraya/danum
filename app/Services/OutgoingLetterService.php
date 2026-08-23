<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\OutgoingLetterStatus;
use App\Models\OutgoingLetter;
use App\Repositories\Contracts\OutgoingLetterRepositoryInterface;
use App\Repositories\Contracts\OutgoingLetterStatusHistoryRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class OutgoingLetterService
{
    public function __construct(
        private readonly OutgoingLetterRepositoryInterface $repository,
        private readonly OutgoingLetterStatusHistoryRepositoryInterface $historyRepository,
    ) {}

    public function getAll(string $tenantId): Collection { return $this->repository->getAll($tenantId); }
    public function find(string $id, string $tenantId): ?OutgoingLetter { return $this->repository->find($id, $tenantId); }
    public function findWithTrashed(string $id, string $tenantId): ?OutgoingLetter { return $this->repository->findWithTrashed($id, $tenantId); }

    public function create(array $data, int $changedBy): OutgoingLetter
    {
        $outgoingLetter = $this->repository->create($data);
        $this->recordHistory($outgoingLetter, 'created', $changedBy);
        return $outgoingLetter;
    }

    public function update(OutgoingLetter $outgoingLetter, array $data): OutgoingLetter
    {
        $this->ensureMutable($outgoingLetter);
        if ($outgoingLetter->status === OutgoingLetterStatus::DRAFT && $outgoingLetter->submitted_at !== null) {
            throw new \DomainException('Surat sudah dikirim untuk verifikasi dan tidak dapat diedit.');
        }
        return $this->repository->update($outgoingLetter, $data);
    }

    public function submit(OutgoingLetter $outgoingLetter, int $changedBy): OutgoingLetter
    {
        if ($outgoingLetter->status !== OutgoingLetterStatus::DRAFT) throw new \DomainException('Hanya draft yang dapat dikirim untuk verifikasi.');
        if ($outgoingLetter->submitted_at !== null) throw new \DomainException('Surat sudah dikirim untuk verifikasi.');
        if ($outgoingLetter->validator_user_id === null) throw new \DomainException('Verifikator belum ditentukan.');

        $outgoingLetter = $this->repository->update($outgoingLetter, ['submitted_at' => now(), 'rejection_reason' => null, 'rejected_by' => null, 'rejected_at' => null]);
        $this->recordHistory($outgoingLetter, 'submitted', $changedBy);
        return $outgoingLetter;
    }

    public function delete(OutgoingLetter $outgoingLetter): bool
    {
        $this->ensureMutable($outgoingLetter);
        if ($outgoingLetter->submitted_at !== null) throw new \DomainException('Surat yang sudah dikirim untuk verifikasi tidak dapat dihapus.');
        return $this->repository->delete($outgoingLetter);
    }

    public function restore(OutgoingLetter $outgoingLetter): bool
    {
        if ($outgoingLetter->status === OutgoingLetterStatus::ISSUED) throw new \DomainException('Issued letters cannot be restored or modified.');
        return $this->repository->restore($outgoingLetter);
    }

    public function validate(OutgoingLetter $outgoingLetter, int $changedBy): OutgoingLetter
    {
        if ($outgoingLetter->status !== OutgoingLetterStatus::DRAFT || $outgoingLetter->submitted_at === null) throw new \DomainException('Surat belum dikirim untuk verifikasi.');
        return $this->transition($outgoingLetter, OutgoingLetterStatus::DRAFT, OutgoingLetterStatus::VALIDATED, $changedBy, ['submitted_at' => null]);
    }

    public function issue(OutgoingLetter $outgoingLetter, int $changedBy): OutgoingLetter
    {
        return $this->transition($outgoingLetter, OutgoingLetterStatus::VALIDATED, OutgoingLetterStatus::ISSUED, $changedBy, ['issued_at' => now()->toDateString()]);
    }

    public function reject(OutgoingLetter $outgoingLetter, int $changedBy, string $reason): OutgoingLetter
    {
        $reason = trim($reason);
        if ($reason === '') throw new \DomainException('Alasan penolakan wajib diisi.');
        if (! in_array($outgoingLetter->status, [OutgoingLetterStatus::DRAFT, OutgoingLetterStatus::VALIDATED], true)) throw new \DomainException('Surat tidak dapat ditolak pada status saat ini.');

        $outgoingLetter = $this->repository->update($outgoingLetter, [
            'status' => OutgoingLetterStatus::DRAFT,
            'submitted_at' => null,
            'rejection_reason' => $reason,
            'rejected_by' => $changedBy,
            'rejected_at' => now(),
        ]);
        $this->recordHistory($outgoingLetter, 'rejected', $changedBy);
        return $outgoingLetter;
    }

    public function cancel(OutgoingLetter $outgoingLetter, int $changedBy): OutgoingLetter
    {
        if ($outgoingLetter->status !== OutgoingLetterStatus::DRAFT || $outgoingLetter->submitted_at !== null) throw new \DomainException('Only editable drafts can be cancelled.');
        $outgoingLetter = $this->repository->update($outgoingLetter, ['status' => OutgoingLetterStatus::CANCELLED]);
        $this->recordHistory($outgoingLetter, 'cancelled', $changedBy);
        return $outgoingLetter;
    }

    private function transition(OutgoingLetter $outgoingLetter, OutgoingLetterStatus $from, OutgoingLetterStatus $to, int $changedBy, array $attributes = []): OutgoingLetter
    {
        if ($outgoingLetter->status !== $from) throw new \DomainException(sprintf('Letter must have %s status.', $from->value));
        $outgoingLetter = $this->repository->update($outgoingLetter, [...$attributes, 'status' => $to]);
        $this->recordHistory($outgoingLetter, $to->value, $changedBy);
        return $outgoingLetter;
    }

    private function ensureMutable(OutgoingLetter $outgoingLetter): void
    {
        if ($outgoingLetter->status !== OutgoingLetterStatus::DRAFT) throw new \DomainException('Only draft letters can be modified.');
    }

    private function recordHistory(OutgoingLetter $outgoingLetter, string $action, int $changedBy): void
    {
        $this->historyRepository->create(['outgoing_letter_id' => $outgoingLetter->id, 'changed_by' => $changedBy, 'status' => $outgoingLetter->status, 'action' => $action]);
    }
}
