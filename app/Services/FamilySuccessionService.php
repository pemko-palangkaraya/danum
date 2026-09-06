<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Citizen;
use App\Models\Family;
use App\Models\FamilyMember;
use App\Models\PopulationEvent;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

final class FamilySuccessionService
{
    private const HEAD = 'head';
    private const SPOUSE = 'spouse';
    private const CHILD = 'child';
    private const PARENT = 'parent';
    private const WIDOWED = 'widowed';

    public function handleMemberDeath(Citizen $citizen, string $eventDate, ?int $actorId = null): void
    {
        $membership = FamilyMember::query()
            ->where('citizen_id', $citizen->id)
            ->where('status', 'active')
            ->whereHas('family', fn ($query) => $query->where('tenant_id', $citizen->tenant_id))
            ->with('family')
            ->first();

        if ($membership === null || $membership->family === null) {
            return;
        }

        $family = $membership->family;

        DB::transaction(function () use ($citizen, $eventDate, $actorId, $family, $membership): void {
            if ($family->head_citizen_id === $citizen->id) {
                $replacement = $this->findLivingSpouse($family, $citizen->id);
                if ($replacement !== null) {
                    $this->promoteSpouse($family, $replacement, $eventDate, $actorId);
                } elseif ($this->bothParentsAreDead($family, $citizen->id)) {
                    $child = $this->findOldestLivingChild($family);
                    if ($child !== null) {
                        $this->promoteChild($family, $child, $eventDate, $actorId);
                    }
                }
            }

            $membership->update([
                'status' => 'inactive',
                'tanggal_selesai' => $eventDate,
            ]);
        });
    }

    private function findLivingSpouse(Family $family, string $deceasedId): ?Citizen
    {
        return $family->activeMembers()
            ->where('hubungan_dalam_keluarga', self::SPOUSE)
            ->whereHas('citizen', fn ($query) => $query
                ->where('tenant_id', $family->tenant_id)
                ->where('id', '!=', $deceasedId)
                ->where('status_kependudukan', '!=', 'meninggal')
            )
            ->with('citizen')
            ->first()?->citizen;
    }

    private function bothParentsAreDead(Family $family, string $deceasedId): bool
    {
        if ($family->head_citizen_id !== $deceasedId) {
            return false;
        }

        $spouse = $family->members()
            ->where('hubungan_dalam_keluarga', self::SPOUSE)
            ->whereHas('citizen', fn ($query) => $query->where('tenant_id', $family->tenant_id))
            ->with('citizen')
            ->get()
            ->pluck('citizen')
            ->filter()
            ->first();

        return $spouse !== null && $spouse->status_kependudukan === 'meninggal';
    }

    private function findOldestLivingChild(Family $family): ?Citizen
    {
        return $family->activeMembers()
            ->where('hubungan_dalam_keluarga', self::CHILD)
            ->whereHas('citizen', fn ($query) => $query
                ->where('tenant_id', $family->tenant_id)
                ->where('status_kependudukan', '!=', 'meninggal')
                ->whereNotNull('tanggal_lahir')
            )
            ->with('citizen')
            ->get()
            ->pluck('citizen')
            ->filter()
            ->sortBy('tanggal_lahir')
            ->first();
    }

    private function promoteSpouse(Family $family, Citizen $spouse, string $eventDate, ?int $actorId): void
    {
        $oldHeadId = $family->head_citizen_id;
        $this->changeHead($family, $spouse, $eventDate, $actorId, 'Pasangan yang masih hidup otomatis menjadi kepala keluarga setelah kepala keluarga meninggal.');
        $this->changeMemberRelationship($family, $oldHeadId, self::SPOUSE);

        $oldStatus = $spouse->status_perkawinan;
        if ($oldStatus === self::WIDOWED) {
            return;
        }

        $spouse->update([
            'status_perkawinan' => self::WIDOWED,
            'updated_by' => $actorId,
        ]);

        $this->recordCitizenEvent(
            $spouse,
            'marital_status_change',
            $eventDate,
            ['status_perkawinan' => $oldStatus],
            ['status_perkawinan' => self::WIDOWED],
            $family->no_kk,
            $actorId,
            'Status perkawinan otomatis berubah menjadi cerai mati setelah pasangan meninggal.',
        );
    }

    private function promoteChild(Family $family, Citizen $child, string $eventDate, ?int $actorId): void
    {
        $this->changeHead($family, $child, $eventDate, $actorId, 'Anak tertua yang masih hidup otomatis menjadi kepala keluarga setelah kedua orang tua meninggal.');

        FamilyMember::query()
            ->where('family_id', $family->id)
            ->where('citizen_id', '!=', $child->id)
            ->where('status', 'active')
            ->whereIn('hubungan_dalam_keluarga', [self::HEAD, self::SPOUSE])
            ->update(['hubungan_dalam_keluarga' => self::PARENT]);
    }

    private function changeMemberRelationship(Family $family, ?string $citizenId, string $relationship): void
    {
        if ($citizenId === null) {
            return;
        }

        FamilyMember::query()
            ->where('family_id', $family->id)
            ->where('citizen_id', $citizenId)
            ->where('status', 'active')
            ->update(['hubungan_dalam_keluarga' => $relationship]);
    }

    private function changeHead(Family $family, Citizen $replacement, string $eventDate, ?int $actorId, string $notes): void
    {
        $oldHeadId = $family->head_citizen_id;
        if ($oldHeadId === $replacement->id) {
            return;
        }

        $family->update([
            'head_citizen_id' => $replacement->id,
            'updated_by' => $actorId,
        ]);

        $this->changeMemberRelationship($family, $replacement->id, self::HEAD);

        PopulationEvent::create([
            'tenant_id' => $family->tenant_id,
            'family_id' => $family->id,
            'citizen_id' => $replacement->id,
            'event_type' => 'family_head_change',
            'event_date' => $eventDate,
            'effective_date' => $eventDate,
            'old_data' => ['head_citizen_id' => $oldHeadId],
            'new_data' => [
                'head_citizen_id' => $replacement->id,
                'no_kk' => $family->no_kk,
            ],
            'notes' => $notes,
            'created_by' => $actorId,
        ]);
    }

    private function recordCitizenEvent(
        Citizen $citizen,
        string $eventType,
        string $eventDate,
        array $oldData,
        array $newData,
        string $documentNumber,
        ?int $actorId,
        string $notes,
    ): void {
        PopulationEvent::create([
            'tenant_id' => $citizen->tenant_id,
            'citizen_id' => $citizen->id,
            'event_type' => $eventType,
            'event_date' => Carbon::parse($eventDate)->toDateString(),
            'effective_date' => Carbon::parse($eventDate)->toDateString(),
            'old_data' => $oldData,
            'new_data' => $newData,
            'document_number' => $documentNumber,
            'notes' => $notes,
            'created_by' => $actorId,
        ]);
    }
}
