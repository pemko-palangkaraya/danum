<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\OutgoingLetterStatus;
use App\Enums\RegisterEntrySource;
use App\Enums\RegisterEntryStatus;
use App\Models\OutgoingLetter;
use App\Models\RegisterEntry;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

final class RegisterEntryService
{
    public function queryForTenant(string $tenantId): Builder
    {
        return RegisterEntry::query()->where('tenant_id', $tenantId)->with(['outgoingLetter', 'registeredBy:id,name']);
    }

    public function registerIssuedLetter(OutgoingLetter $letter): RegisterEntry
    {
        if ($letter->status !== OutgoingLetterStatus::ISSUED) throw new \DomainException('Hanya surat yang sudah diterbitkan yang dapat masuk buku register.');

        return DB::transaction(function () use ($letter): RegisterEntry {
            $existing = RegisterEntry::query()->where('outgoing_letter_id', $letter->id)->first();
            if ($existing) return $existing;

            Tenant::query()->whereKey($letter->tenant_id)->lockForUpdate()->firstOrFail();
            $year = (int) ($letter->issued_at?->year ?? $letter->letter_date?->year ?? now()->year);
            $registerNumber = ((int) RegisterEntry::query()->where('tenant_id', $letter->tenant_id)->where('register_year', $year)->lockForUpdate()->max('register_number')) + 1;
            $entry = RegisterEntry::query()->create([
                'tenant_id' => $letter->tenant_id,
                'outgoing_letter_id' => $letter->id,
                'registered_by' => $letter->signer_user_id,
                'register_number' => $registerNumber,
                'register_year' => $year,
                'letter_number' => (string) $letter->number,
                'letter_date' => $letter->letter_date ?? $letter->issued_at ?? now()->toDateString(),
                'letter_type_name' => $letter->letterType?->name,
                'classification_code' => $letter->letterType?->classification?->code,
                'subject' => (string) $letter->subject,
                'recipient_name' => (string) $letter->recipient_name,
                'recipient_address' => $letter->recipient_address,
                'signer_name' => $letter->signer_name,
                'signer_title' => $letter->signer_title,
                'source' => RegisterEntrySource::DANUM,
                'status' => RegisterEntryStatus::REGISTERED,
            ]);
            $actor = $letter->signerUser()->first();
            if ($actor) app(AuditLogService::class)->record('register_entry.created', $actor, $entry, null, $entry->only(['tenant_id', 'outgoing_letter_id', 'register_number', 'register_year', 'letter_number', 'source', 'status']));
            return $entry;
        });
    }

    public function createManual(array $data, User $user): RegisterEntry
    {
        $tenantId = $user->tenant_id;
        if ($tenantId === null) throw new \DomainException('Surat manual hanya dapat dicatat oleh pengguna tenant.');
        $letterNumber = trim((string) ($data['letter_number'] ?? ''));
        if ($letterNumber === '') throw new \DomainException('Nomor surat wajib diisi.');

        return DB::transaction(function () use ($data, $user, $tenantId, $letterNumber): RegisterEntry {
            $date = now()->parse($data['letter_date']);
            $year = (int) $date->year;
            Tenant::query()->whereKey($tenantId)->lockForUpdate()->firstOrFail();
            if (RegisterEntry::query()->where('tenant_id', $tenantId)->where('letter_number', $letterNumber)->exists()) throw new \DomainException('Nomor surat tersebut sudah tercatat dalam buku register tenant ini.');
            $registerNumber = ((int) RegisterEntry::query()->where('tenant_id', $tenantId)->where('register_year', $year)->lockForUpdate()->max('register_number')) + 1;
            $entry = RegisterEntry::query()->create([
                'tenant_id' => $tenantId,
                'registered_by' => $user->id,
                'register_number' => $registerNumber,
                'register_year' => $year,
                'letter_number' => $letterNumber,
                'letter_date' => $date->toDateString(),
                'letter_type_name' => $data['letter_type_name'] ?? null,
                'classification_code' => $data['classification_code'] ?? null,
                'subject' => trim((string) $data['subject']),
                'recipient_name' => trim((string) $data['recipient_name']),
                'recipient_address' => $data['recipient_address'] ?? null,
                'signer_name' => $data['signer_name'] ?? null,
                'signer_title' => $data['signer_title'] ?? null,
                'source' => RegisterEntrySource::MANUAL,
                'status' => RegisterEntryStatus::REGISTERED,
            ]);
            app(AuditLogService::class)->record('register_entry.created', $user, $entry, null, $entry->only(['tenant_id', 'register_number', 'register_year', 'letter_number', 'source', 'status']));
            return $entry;
        });
    }

    public function correct(RegisterEntry $entry, array $data, User $user): RegisterEntry
    {
        if ($entry->tenant_id !== $user->tenant_id && ! $user->isSuperAdmin()) throw new \DomainException('Register tidak berada dalam tenant Anda.');
        $reason = trim((string) ($data['correction_reason'] ?? ''));
        if ($reason === '') throw new \DomainException('Alasan koreksi wajib diisi.');
        $old = $entry->only(['letter_number', 'letter_date', 'letter_type_name', 'classification_code', 'subject', 'recipient_name', 'recipient_address', 'signer_name', 'signer_title'])->toArray();

        return DB::transaction(function () use ($entry, $data, $reason, $old, $user): RegisterEntry {
            $entry->fill([
                'letter_number' => $data['letter_number'] ?? $entry->letter_number,
                'letter_date' => $data['letter_date'] ?? $entry->letter_date,
                'letter_type_name' => $data['letter_type_name'] ?? $entry->letter_type_name,
                'classification_code' => $data['classification_code'] ?? $entry->classification_code,
                'subject' => $data['subject'] ?? $entry->subject,
                'recipient_name' => $data['recipient_name'] ?? $entry->recipient_name,
                'recipient_address' => $data['recipient_address'] ?? $entry->recipient_address,
                'signer_name' => $data['signer_name'] ?? $entry->signer_name,
                'signer_title' => $data['signer_title'] ?? $entry->signer_title,
                'status' => RegisterEntryStatus::CORRECTED,
                'correction_reason' => $reason,
            ]);
            $entry->save();
            app(AuditLogService::class)->record('register_entry.corrected', $user, $entry, $old, $entry->only(['letter_number', 'letter_date', 'letter_type_name', 'classification_code', 'subject', 'recipient_name', 'recipient_address', 'signer_name', 'signer_title', 'status', 'correction_reason']));
            return $entry->refresh();
        });
    }
}
