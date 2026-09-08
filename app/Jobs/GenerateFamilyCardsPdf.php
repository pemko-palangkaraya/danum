<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\FamilyCardExport;
use App\Models\Tenant;
use App\Services\FamilyCardsBulkPdfService;
use App\Services\PopulationReferenceService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Throwable;

class GenerateFamilyCardsPdf implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 900;

    public function __construct(public string $exportId)
    {
    }

    public function handle(
        FamilyCardsBulkPdfService $bulkPdf,
        PopulationReferenceService $references,
    ): void {
        @ini_set('memory_limit', '512M');
        @set_time_limit(0);

        $export = FamilyCardExport::query()->find($this->exportId);

        if ($export === null) {
            return;
        }

        $export->update(['status' => 'processing', 'error' => null]);

        try {
            $tenant = Tenant::query()->findOrFail($export->tenant_id);
            $content = $bulkPdf->generate($tenant, $references);
            $path = 'exports/family-cards/' . $export->id . '.pdf';

            Storage::disk('local')->put($path, $content);

            $export->update([
                'status' => 'completed',
                'path' => $path,
            ]);
        } catch (Throwable $exception) {
            $export->update([
                'status' => 'failed',
                'error' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }
}
