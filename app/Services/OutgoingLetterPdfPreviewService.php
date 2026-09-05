<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\OutgoingLetterStatus;
use App\Models\OutgoingLetter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class OutgoingLetterPdfPreviewService
{
    public function __construct(
        private readonly DocxPdfService $docxPdfService,
        private readonly DocxTteService $docxTteService,
        private readonly PdfPreviewWatermarkService $pdfPreviewWatermarkService,
    ) {}

    public function respond(OutgoingLetter $letter, Request $request): BinaryFileResponse
    {
        $filename = sprintf('surat-%s.pdf', str($letter->number)->slug());

        if ($letter->status === OutgoingLetterStatus::ISSUED) {
            $pdfPath = $letter->signed_pdf_path ?: $letter->unsigned_pdf_path;
            if (blank($pdfPath) || ! Storage::disk('local')->exists($pdfPath)) {
                abort(422, 'PDF final surat belum tersedia.');
            }

            $disposition = $request->boolean('download') ? 'attachment' : 'inline';

            return response()->file(
                Storage::disk('local')->path($pdfPath),
                [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => sprintf('%s; filename="%s"', $disposition, $filename),
                ],
            );
        }

        $letter->loadMissing([
            'tenant',
            'letterType',
            'letterTypeVersion',
            'signerPosition',
            'signerUser',
            'validatorPosition',
            'validatorUser',
        ]);

        $sourceDocxPath = $letter->generated_docx_path;
        if (! $sourceDocxPath || ! Storage::disk('local')->exists($sourceDocxPath)) {
            abort(422, 'DOCX hasil surat belum tersedia. Buat ulang draft surat terlebih dahulu.');
        }

        $temporaryDocx = null;
        $pdfPath = null;

        try {
            $temporaryDocx = $this->docxTteService->createPreviewCopy(
                Storage::disk('local')->path($sourceDocxPath),
            );
            $pdfPath = $this->docxPdfService->convert($temporaryDocx);
            $absolutePath = Storage::disk('local')->path($pdfPath);
            $label = sprintf('%s | %s | PREVIEW', $request->user()->name, now()->format('d-m-Y'));
            $watermarked = $this->pdfPreviewWatermarkService->apply($absolutePath, $label);
            $previewFilename = sprintf('preview-surat-%s.pdf', str($letter->number)->slug());

            return response()->file(
                $watermarked,
                [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => sprintf('inline; filename="%s"', $previewFilename),
                ],
            )->deleteFileAfterSend(true);
        } catch (\RuntimeException $exception) {
            abort(422, $exception->getMessage());
        } finally {
            if ($temporaryDocx !== null) {
                @unlink($temporaryDocx);
            }

            if ($pdfPath !== null) {
                Storage::disk('local')->delete($pdfPath);
            }
        }
    }
}
