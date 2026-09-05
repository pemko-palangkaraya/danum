<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Citizen;
use App\Models\OutgoingLetter;
use App\Models\PopulationEvent;
use Illuminate\Support\Carbon;
use RuntimeException;

final class CitizenDeathService
{
    public const LETTER_TYPE_CODE = 'SURAT_KETERANGAN_KEMATIAN';
    public const DATE_VARIABLE = 'tanggal_meninggal';

    private const MONTHS = [
        'jan' => 1, 'feb' => 2, 'mar' => 3, 'apr' => 4, 'mei' => 5, 'jun' => 6,
        'jul' => 7, 'agu' => 8, 'sep' => 9, 'okt' => 10, 'nov' => 11, 'des' => 12,
    ];

    public function applyFromIssuedLetter(OutgoingLetter $letter): void
    {
        if ($letter->status?->value !== 'issued') return;
        $citizenId = $letter->getAttribute('citizen_id');
        if (blank($citizenId)) return;
        if ((string) $letter->letterType?->code !== self::LETTER_TYPE_CODE) return;

        $rawTanggalMeninggal = data_get($letter->input_data, self::DATE_VARIABLE);
        if (blank($rawTanggalMeninggal)) {
            throw new RuntimeException('Surat keterangan kematian yang diterbitkan wajib memiliki tanggal_meninggal.');
        }

        $tanggalMeninggal = $this->normalizeDate($rawTanggalMeninggal);
        if ($tanggalMeninggal === null) {
            throw new RuntimeException('Tanggal meninggal tidak valid. Gunakan format dd mmm yyyy.');
        }

        $citizen = Citizen::query()->where('tenant_id', $letter->tenant_id)->findOrFail($citizenId);
        if ($citizen->tanggal_lahir && Carbon::parse($tanggalMeninggal)->lt($citizen->tanggal_lahir)) {
            throw new RuntimeException('Tanggal meninggal tidak boleh lebih awal dari tanggal lahir warga.');
        }

        if ($citizen->status_kependudukan === 'meninggal') {
            if ($citizen->tanggal_meninggal?->toDateString() !== $tanggalMeninggal) {
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
            'tanggal_meninggal' => $tanggalMeninggal,
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

    private function normalizeDate(mixed $value): ?string
    {
        $value = trim((string) $value);
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) return $value;
        if (! preg_match('/^(\d{1,2})\s+([A-Za-z]{3,4})\s+(\d{4})$/u', $value, $matches)) return null;
        $month = self::MONTHS[mb_strtolower($matches[2])] ?? null;
        if (! $month) return null;
        try {
            return Carbon::createFromFormat('!j-n-Y', $matches[1].'-'.$month.'-'.$matches[3])->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }
}
