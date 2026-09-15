<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\OutgoingLetter;
use Illuminate\Support\Facades\Storage;

final class OutgoingLetterDocumentService
{
    public function __construct(
        private readonly DocxTemplateService $docx,
        private readonly OutgoingLetterAttachmentService $attachments,
    ) {}

    public function regenerate(OutgoingLetter $letter): OutgoingLetter
    {
        $letterType = $letter->letterType()->first();
        if (! $letterType) throw new \DomainException('Jenis surat tidak ditemukan.');

        $version = $letter->letterTypeVersion()->first() ?? $letterType->currentVersion();
        $templatePath = $version?->template_path ?: $letterType->template_path;
        if (blank($templatePath)) throw new \DomainException('Template DOCX surat belum tersedia.');

        $absoluteTemplate = Storage::disk('local')->path($templatePath);
        if (! is_file($absoluteTemplate)) throw new \DomainException('File template DOCX surat tidak ditemukan.');

        $data = $letter->input_data ?? [];
        $data['number'] = $letter->number;
        $data['recipient_name'] = $letter->recipient_name;
        $data['recipient_address'] = $letter->recipient_address;
        $data['subject'] = $letter->subject;
        $data['date'] = optional($letter->letter_date)->format('Y-m-d');
        $data['tenant_head_name'] = (string) ($letter->signer_name ?? '');
        $data['tenant_head_title'] = (string) ($letter->signer_title ?? '');
        $data['nama_ttd'] = (string) ($letter->signer_name ?? '');
        $data['jabatan_ttd'] = (string) ($letter->signer_title ?? '');
        $data['lampiran'] = $this->attachments->label($letter) ?? '';

        $path = $this->docx->renderToStorage($absoluteTemplate, $letter->tenant, $data, $letterType->font_family);
        $oldPath = $letter->generated_docx_path;

        $letter->update(['generated_docx_path' => $path]);
        if ($oldPath && $oldPath !== $path) Storage::disk('local')->delete($oldPath);

        return $letter->refresh();
    }
}
