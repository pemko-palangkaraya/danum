<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Citizen;
use App\Models\OutgoingLetter;
use App\Models\PopulationEvent;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class CitizenDeathService
{
    public const LETTER_TYPE_CODE = 'SURAT_KETERANGAN_KEMATIAN';
    public const DATE_VARIABLE = 'tanggal_meninggal';

    public function applyFromIssuedLetter(OutgoingLetter $letter): void
    {
        if ($letter->status?->value !== 'issued') {
            return;
        }

        $citizenId = $letter->getAttribute('citizen_id');
        if (blank($citizenId)) {
            return;
        }

        if ((string) $letter->letterType?->code !== self::LETTER_TYPE_CODE) {
            return;
        }

        $tanggalMeninggal = data_get($letter->input_data, self::DATE_VARIABLE);
        if (blank($tanggalMeninggal)) {
            throw new RuntimeException('Surat keterangan kematian yang diterbitkan wajib memiliki tanggal_meninggal.');
        }

        $citizen = Citizen::query()->where('tenant_id', $letter->tenant_id)->findOrFail($citizenId);

        if ($citizen->status_kependudukan === 'meninggal') {
            if ($citizen->tanggal_meninggal?->toDateString() !== (string) $tanggalMeninggal) {
                throw new RuntimeException('Data warga sudah berstatus meninggal dengan tanggal yang berbeda.');
            }

            return;
        }

        $oldData = [
            'status_kependudukan' => $citizen->status_kependudukan,
            'tanggal_meninggal' => $citizen->tanggal_meninggal?->toDateString(),
        ];
        $newData = [
            'status_kependudukan' => 'meninggal',
            'tanggal_meninggal' => (string) $tanggalMeninggal,
            'outgoing_letter_id' => $letter->id,
            'document_number' => $letter->number,
        ];

        $citizen->update([
            'status_kependudukan' => 'meninggal',
            'tanggal_meninggal' => $tanggalMeninggal,
            'updated_by' => $letter->created_by,
        ]);

        PopulationEvent::create([
            'tenant_id' => $citizen->tenant_id,
            'citizen_id' => $citizen->id,
            'event_type' => 'death',
            'event_date' => $tanggalMeninggal,
            'effective_date' => $tanggalMeninggal,
            'old_data' => $oldData,
            'new_data' => $newData,
            'document_number' => $letter->number,
            'notes' => 'Status kependudukan diperbarui otomatis setelah Surat Keterangan Kematian diterbitkan.',
            'created_by' => $letter->created_by,
        ]);
    }
}
