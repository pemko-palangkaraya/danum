<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\OutgoingLetter;
use App\Models\OutgoingLetterAttachment;
use App\Support\Pdf\FileBufferedFpdi;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use setasign\Fpdi\Fpdi;

final class OutgoingLetterAttachmentService
{
    private const MAX_ATTACHMENTS = 20;
    private const MAX_FILE_SIZE = 20 * 1024 * 1024;
    private const HEADER_HEIGHT = 30.0;

    public function addUploads(OutgoingLetter $letter, array $files, array $titles = []): void
    {
        $files = array_values(array_filter($files));
        if ($files === []) return;

        $existingCount = $letter->attachments()->count();
        if ($existingCount + count($files) > self::MAX_ATTACHMENTS) {
            throw new \DomainException('Maksimal 20 lampiran untuk satu surat.');
        }

        $nextSequence = $existingCount + 1;
        $stored = [];

        try {
            foreach ($files as $index => $file) {
                if (! $file instanceof UploadedFile) {
                    throw new \DomainException('File lampiran tidak valid.');
                }
                if (! $file->isValid()) {
                    throw new \DomainException('Salah satu file lampiran gagal diunggah.');
                }
                if (strtolower((string) $file->getClientOriginalExtension()) !== 'pdf' || $file->getMimeType() !== 'application/pdf') {
                    throw new \DomainException('Lampiran V1 hanya menerima file PDF.');
                }
                if (($file->getSize() ?? 0) > self::MAX_FILE_SIZE) {
                    throw new \DomainException('Ukuran setiap lampiran maksimal 20 MB.');
                }

                $pageCount = $this->countPages($file->getRealPath());
                if ($pageCount < 1) {
                    throw new \DomainException('PDF lampiran tidak memiliki halaman yang dapat dibaca.');
                }

                $originalName = (string) $file->getClientOriginalName();
                $filename = Str::uuid()->toString() . '.pdf';
                $directory = 'outgoing-letters/attachments/' . $letter->tenant_id . '/' . $letter->id;
                $path = Storage::disk('local')->putFileAs($directory, $file, $filename);
                if ($path === false) {
                    throw new RuntimeException('File lampiran gagal disimpan.');
                }
                $stored[] = $path;

                $title = trim((string) ($titles[$index] ?? ''));
                $title = $title !== '' ? $title : pathinfo($originalName, PATHINFO_FILENAME);

                $attachment = $letter->attachments()->create([
                    'sequence' => $nextSequence++,
                    'title' => Str::limit($title, 255, ''),
                    'source' => 'uploaded',
                    'file_path' => $path,
                    'original_name' => Str::limit($originalName, 255, ''),
                    'mime_type' => 'application/pdf',
                    'page_count' => $pageCount,
                    'file_size' => $file->getSize(),
                ]);

                $this->audit('outgoing_letter.attachment_added', $letter, $attachment, [
                    'sequence' => $attachment->sequence,
                    'title' => $attachment->title,
                    'page_count' => $attachment->page_count,
                    'original_name' => $attachment->original_name,
                ]);
            }
        } catch (\Throwable $exception) {
            foreach ($stored as $path) Storage::disk('local')->delete($path);
            throw $exception;
        }
    }

    public function delete(OutgoingLetterAttachment $attachment): void
    {
        $letter = $attachment->outgoingLetter;
        $old = [
            'sequence' => $attachment->sequence,
            'title' => $attachment->title,
            'page_count' => $attachment->page_count,
            'original_name' => $attachment->original_name,
        ];

        Storage::disk('local')->delete($attachment->file_path);
        $attachment->delete();
        $this->resequnce($letter);
        $this->audit('outgoing_letter.attachment_deleted', $letter, $letter, $old);
    }

    public function move(OutgoingLetterAttachment $attachment, int $direction): void
    {
        $letter = $attachment->outgoingLetter;
        $attachments = $letter->attachments()->orderBy('sequence')->get();
        $index = $attachments->search(fn (OutgoingLetterAttachment $item): bool => (string) $item->id === (string) $attachment->id);
        if ($index === false) return;

        $target = $index + $direction;
        if ($target < 0 || $target >= $attachments->count()) return;

        $items = $attachments->all();
        [$items[$index], $items[$target]] = [$items[$target], $items[$index]];
        foreach ($items as $position => $item) $item->update(['sequence' => $position + 1]);

        $this->audit('outgoing_letter.attachment_reordered', $letter, $letter, [
            'order' => $attachments->pluck('id')->values()->all(),
        ], [
            'order' => collect($items)->pluck('id')->values()->all(),
        ]);
    }

    public function totalPages(OutgoingLetter $letter): int
    {
        return (int) $letter->attachments()->sum('page_count');
    }

    public function label(OutgoingLetter $letter): ?string
    {
        $pages = $this->totalPages($letter);
        return $pages > 0 ? 'Lampiran : ' . $pages . ' (' . $this->numberToWords($pages) . ') lembar' : null;
    }

    public function combineWithMainPdf(string $mainPdfPath, OutgoingLetter $letter, ?string $outputRelativePath = null): string
    {
        $attachments = $letter->attachments()->orderBy('sequence')->get();
        if ($attachments->isEmpty()) return $mainPdfPath;
        if (! is_file($mainPdfPath)) throw new RuntimeException('PDF utama tidak ditemukan untuk penggabungan lampiran.');

        $output = $outputRelativePath !== null
            ? Storage::disk('local')->path($outputRelativePath)
            : tempnam(sys_get_temp_dir(), 'danum-letter-package-') . '.pdf';

        if ($output === false || $output === null) throw new RuntimeException('File PDF gabungan tidak dapat dibuat.');
        if ($outputRelativePath !== null) Storage::disk('local')->makeDirectory(dirname($outputRelativePath));

        $pdf = new FileBufferedFpdi();
        $pdf->SetAutoPageBreak(false);
        $pdf->openFile($output);

        $this->appendPdf($pdf, $mainPdfPath);
        foreach ($attachments as $attachment) {
            $absolutePath = Storage::disk('local')->path($attachment->file_path);
            if (! is_file($absolutePath)) throw new RuntimeException('File lampiran tidak ditemukan: ' . $attachment->original_name);
            $this->appendPdf($pdf, $absolutePath, $letter, $attachment->sequence);
        }

        $pdf->Output('F', $output);
        if (! is_file($output)) throw new RuntimeException('PDF gabungan lampiran gagal dibuat.');
        return $output;
    }

    public function countPages(?string $path): int
    {
        if (! $path || ! is_file($path)) throw new RuntimeException('PDF lampiran tidak ditemukan.');
        $reader = new Fpdi();
        return (int) $reader->setSourceFile($path);
    }

    private function appendPdf(FileBufferedFpdi $pdf, string $path, ?OutgoingLetter $letter = null, ?int $sequence = null): void
    {
        $pageCount = $pdf->setSourceFile($path);
        for ($page = 1; $page <= $pageCount; $page++) {
            $template = $pdf->importPage($page);
            $size = $pdf->getTemplateSize($template);
            $width = (float) ($size['width'] ?? $size['w'] ?? 210);
            $height = (float) ($size['height'] ?? $size['h'] ?? 297);
            $orientation = $width > $height ? 'L' : 'P';
            $pdf->AddPage($orientation, [$width, $height]);

            if ($page === 1 && $letter !== null && $sequence !== null) {
                $this->drawAttachmentHeader($pdf, $letter, $sequence, $width, $height);
                $availableHeight = max(1.0, $height - self::HEADER_HEIGHT);
                $scale = min(1.0, $width / $width, $availableHeight / $height);
                $renderWidth = $width * $scale;
                $renderHeight = $height * $scale;
                $x = ($width - $renderWidth) / 2;
                $y = self::HEADER_HEIGHT + ($availableHeight - $renderHeight) / 2;
                $pdf->useTemplate($template, $x, $y, $renderWidth, $renderHeight);
            } else {
                $pdf->useTemplate($template, 0, 0, $width, $height);
            }
        }
    }

    private function drawAttachmentHeader(FileBufferedFpdi $pdf, OutgoingLetter $letter, int $sequence, float $width, float $height): void
    {
        $pdf->SetFont('Helvetica', 'B', 12);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetXY(15, 7);
        $pdf->Cell($width - 30, 5, $this->toPdfText('Lampiran ' . $this->roman($sequence)), 0, 1, 'L');

        $tenantName = trim((string) ($letter->tenant?->name ?? ''));
        $signerTitle = trim((string) ($letter->signer_title ?? ''));
        $heading = trim('Surat ' . $signerTitle . ' ' . $tenantName);
        $hal = trim((string) ($letter->input_data['hal'] ?? $letter->subject ?? ''));
        $status = $letter->status?->value ?? '';
        $statusLabel = match ($status) {
            'draft' => 'Draft',
            'submitted' => 'Diajukan',
            'validated' => 'Tervalidasi',
            'issued' => 'Diterbitkan',
            'withdrawn' => 'Ditarik',
            'rejected' => 'Ditolak',
            default => $status,
        };

        $pdf->SetFont('Helvetica', '', 10);
        $pdf->SetXY(15, 12);
        $pdf->Cell($width - 30, 4, $this->toPdfText($heading), 0, 1, 'L');

        $pdf->SetFont('Helvetica', '', 9);
        $this->headerRow($pdf, 'Nomor', (string) $letter->number, $width, 17);
        $this->headerRow($pdf, 'Tanggal', optional($letter->letter_date)->locale('id')->translatedFormat('d F Y') ?? '-', $width, 21);
        $this->headerRow($pdf, 'Hal', $hal !== '' ? $hal : '-', $width, 25);
        $this->headerRow($pdf, 'Status', $statusLabel !== '' ? $statusLabel : '-', $width, 29);

        $pdf->Line(15, self::HEADER_HEIGHT + 2, $width - 15, self::HEADER_HEIGHT + 2);
    }

    private function headerRow(FileBufferedFpdi $pdf, string $label, string $value, float $width, float $y): void
    {
        $pdf->SetXY(15, $y);
        $pdf->Cell(24, 4, $this->toPdfText($label), 0, 0, 'L');
        $pdf->Cell(4, 4, ':', 0, 0, 'L');
        $pdf->Cell($width - 43, 4, $this->toPdfText($value), 0, 1, 'L');
    }

    private function resequnce(OutgoingLetter $letter): void
    {
        foreach ($letter->attachments()->orderBy('sequence')->get() as $index => $item) {
            $item->update(['sequence' => $index + 1]);
        }
    }

    private function audit(string $action, OutgoingLetter $letter, OutgoingLetter|OutgoingLetterAttachment $auditable, ?array $old = null, ?array $new = null): void
    {
        $user = Auth::user();
        if (! $user) return;
        app(AuditLogService::class)->record($action, $user, $auditable, $old, $new, $letter->tenant_id);
    }

    private function roman(int $number): string
    {
        $map = [10 => 'X', 9 => 'IX', 5 => 'V', 4 => 'IV', 1 => 'I'];
        $result = '';
        foreach ($map as $value => $symbol) {
            while ($number >= $value) {
                $result .= $symbol;
                $number -= $value;
            }
        }
        return $result;
    }

    private function numberToWords(int $number): string
    {
        $words = [0 => 'nol', 1 => 'satu', 2 => 'dua', 3 => 'tiga', 4 => 'empat', 5 => 'lima', 6 => 'enam', 7 => 'tujuh', 8 => 'delapan', 9 => 'sembilan', 10 => 'sepuluh', 11 => 'sebelas'];
        if (isset($words[$number])) return $words[$number];
        if ($number < 20) return $words[$number - 10] . ' belas';
        if ($number < 100) return $words[intdiv($number, 10)] . ' puluh' . ($number % 10 ? ' ' . $words[$number % 10] : '');
        if ($number < 200) return 'seratus' . ($number % 100 ? ' ' . $this->numberToWords($number - 100) : '');
        if ($number < 1000) return $words[intdiv($number, 100)] . ' ratus' . ($number % 100 ? ' ' . $this->numberToWords($number % 100) : '');
        return (string) $number;
    }

    private function toPdfText(string $value): string
    {
        return iconv('UTF-8', 'windows-1252//TRANSLIT', $value) ?: $value;
    }
}
