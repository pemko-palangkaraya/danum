<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\OutgoingLetterStatus;
use App\Enums\OutgoingLetterWithdrawalStatus;
use App\Models\OutgoingLetter;
use App\Models\OutgoingLetterWithdrawalRequest;
use App\Repositories\Contracts\OutgoingLetterRepositoryInterface;
use App\Repositories\Contracts\OutgoingLetterStatusHistoryRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

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
        $letterType = $outgoingLetter->letterType()->first();
        $attributes = ['issued_at' => now()->toDateString(), 'valid_from' => now()];
        if ($letterType?->has_expiry) {
            if (! $letterType->validity_days) throw new \DomainException('Jenis surat belum memiliki masa berlaku yang valid.');
            $attributes['valid_until'] = now()->addDays($letterType->validity_days);
        } else {
            $attributes['valid_until'] = null;
        }
        return $this->transition($outgoingLetter, OutgoingLetterStatus::VALIDATED, OutgoingLetterStatus::ISSUED, $changedBy, $attributes);
    }

    public function reject(OutgoingLetter $outgoingLetter, int $changedBy, string $reason): OutgoingLetter
    {
        $reason = trim($reason);
        if ($reason === '') throw new \DomainException('Alasan penolakan wajib diisi.');
        if (! in_array($outgoingLetter->status, [OutgoingLetterStatus::DRAFT, OutgoingLetterStatus::VALIDATED], true)) throw new \DomainException('Surat tidak dapat ditolak pada status saat ini.');

        $outgoingLetter = $this->repository->update($outgoingLetter, ['status' => OutgoingLetterStatus::DRAFT, 'submitted_at' => null, 'rejection_reason' => $reason, 'rejected_by' => $changedBy, 'rejected_at' => now()]);
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

    public function requestWithdrawal(OutgoingLetter $outgoingLetter, int $requestedBy, string $reason, string $statementPath): OutgoingLetterWithdrawalRequest
    {
        $reason = trim($reason);
        if ($outgoingLetter->status !== OutgoingLetterStatus::ISSUED) throw new \DomainException('Hanya surat yang sudah diterbitkan yang dapat diajukan untuk penarikan.');
        if ($reason === '') throw new \DomainException('Alasan penarikan wajib diisi.');
        if ($outgoingLetter->withdrawalRequests()->where('status', OutgoingLetterWithdrawalStatus::PENDING)->exists()) throw new \DomainException('Pengajuan penarikan sedang menunggu persetujuan.');

        $request = OutgoingLetterWithdrawalRequest::query()->create([
            'outgoing_letter_id' => $outgoingLetter->id,
            'requested_by' => $requestedBy,
            'requested_at' => now(),
            'reason' => $reason,
            'statement_path' => $statementPath,
            'status' => OutgoingLetterWithdrawalStatus::PENDING,
        ]);
        $this->recordHistory($outgoingLetter, 'withdrawal_requested', $requestedBy);
        return $request;
    }

    public function approveWithdrawal(OutgoingLetterWithdrawalRequest $request, int $decidedBy, ?string $note = null): OutgoingLetter
    {
        return DB::transaction(function () use ($request, $decidedBy, $note): OutgoingLetter {
            if ($request->status !== OutgoingLetterWithdrawalStatus::PENDING) throw new \DomainException('Pengajuan penarikan sudah diputuskan.');
            $letter = $request->outgoingLetter()->lockForUpdate()->firstOrFail();
            if ($letter->status !== OutgoingLetterStatus::ISSUED) throw new \DomainException('Surat tidak lagi dapat ditarik.');
            $request->update(['status' => OutgoingLetterWithdrawalStatus::APPROVED, 'decided_by' => $decidedBy, 'decided_at' => now(), 'decision_note' => $note]);
            $letter = $this->repository->update($letter, ['status' => OutgoingLetterStatus::WITHDRAWN]);
            $this->recordHistory($letter, 'withdrawn', $decidedBy);
            return $letter;
        });
    }

    public function rejectWithdrawal(OutgoingLetterWithdrawalRequest $request, int $decidedBy, string $note): OutgoingLetterWithdrawalRequest
    {
        $note = trim($note);
        if ($request->status !== OutgoingLetterWithdrawalStatus::PENDING) throw new \DomainException('Pengajuan penarikan sudah diputuskan.');
        if ($note === '') throw new \DomainException('Catatan penolakan wajib diisi.');
        $request->update(['status' => OutgoingLetterWithdrawalStatus::REJECTED, 'decided_by' => $decidedBy, 'decided_at' => now(), 'decision_note' => $note]);
        $this->recordHistory($request->outgoingLetter, 'withdrawal_rejected', $decidedBy);
        return $request->refresh();
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
