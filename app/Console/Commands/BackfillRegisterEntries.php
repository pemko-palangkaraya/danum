<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\OutgoingLetterStatus;
use App\Models\OutgoingLetter;
use App\Services\RegisterEntryService;
use Illuminate\Console\Command;

final class BackfillRegisterEntries extends Command
{
    protected $signature = 'register:backfill {--tenant= : Backfill one tenant UUID only}';
    protected $description = 'Create missing Buku Register entries for issued DANUM letters.';

    public function handle(RegisterEntryService $service): int
    {
        $query = OutgoingLetter::query()
            ->where('status', OutgoingLetterStatus::ISSUED)
            ->whereDoesntHave('registerEntry')
            ->orderBy('issued_at')
            ->orderBy('created_at');

        if ($this->option('tenant')) {
            $query->where('tenant_id', $this->option('tenant'));
        }

        $created = 0;
        $failed = 0;

        $query->chunkById(100, function ($letters) use ($service, &$created, &$failed): void {
            foreach ($letters as $letter) {
                try {
                    $service->registerIssuedLetter($letter->fresh(['letterType.classification']));
                    $created++;
                } catch (\Throwable $exception) {
                    $failed++;
                    $this->error("{$letter->id}: {$exception->getMessage()}");
                }
            }
        }, 'id');

        $this->info("Register backfill selesai. Dibuat: {$created}; gagal: {$failed}.");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
