<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\OutgoingLetterStatus;
use App\Models\LetterType;
use App\Models\OutgoingLetter;
use App\Models\Position;
use App\Models\PositionHolder;
use DOMDocument;
use DOMXPath;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ZipArchive;

final class OutgoingLetterDraftService
{
    public function __construct(
        private readonly OutgoingLetterService $letters,
        private readonly DocxTemplateService $docx,
        private readonly LetterTypeService $letterTypes,
    ) {}

    /**
     * @param array<string,mixed> $data
     */
    public function save(
        ?OutgoingLetter $existing,
        LetterType $letterType,
        Position $signerPosition,
        PositionHolder $signerHolder,
        Position $validatorPosition,
        PositionHolder $validatorHolder,
        array $data,
        int|string $userId,
        string $tenantId,
        mixed $tenant,
    ): string {
        $version = $this->letterTypes->activeVersion($letterType);
        $templateRelativePath = $version?->template_path ?: $letterType->template_path;
        if (! $templateRelativePath) {
            throw new \DomainException('Template DOCX surat belum tersedia.');
        }

        $templatePath = Storage::disk('local')->path($templateRelativePath);
        if (! is_file($templatePath)) {
            throw new \DomainException('File template DOCX tidak ditemukan di storage.');
        }

        $verificationToken = $existing?->verification_token ?? Str::random(64);
        $signerName = (string) ($signerHolder->user?->name ?? '');
        $signerTitle = (string) ($signerPosition->name ?? '');

        $renderData = [
            ...$data,
            'tenant_head_name' => $signerName,
            'tenant_head_title' => $signerTitle,
            'nama_ttd' => $signerName,
            'jabatan_ttd' => $signerTitle,
        ];

        $generatedPath = $this->docx->renderToStorage($templatePath, $tenant, $renderData);

        try {
            $content = $this->extractText(Storage::disk('local')->path($generatedPath));

            $attributes = [
                'tenant_id' => $tenantId,
                'letter_type_id' => $letterType->id,
                'letter_type_version_id' => $version?->id,
                'signer_position_id' => $signerPosition->id,
                'signer_user_id' => $signerHolder->user_id,
                'signer_name' => $signerHolder->user->name,
                'signer_title' => $signerPosition->name,
                'validator_position_id' => $validatorPosition->id,
                'validator_user_id' => $validatorHolder->user_id,
                'validator_name' => $validatorHolder->user->name,
                'validator_title' => $validatorPosition->name,
                'number' => (string) ($data['number'] ?? ''),
                'recipient_name' => (string) ($data['recipient_name'] ?? ''),
                'recipient_address' => (string) ($data['recipient_address'] ?? ''),
                'subject' => (string) ($data['subject'] ?? ''),
                'letter_date' => $data['date'] ?? null,
                'generated_docx_path' => $generatedPath,
                'verification_token' => $verificationToken,
                'content' => $content,
                'input_data' => $data,
            ];

            if ($existing) {
                $oldPath = $existing->generated_docx_path;
                $this->letters->update($existing, $attributes);
                if ($oldPath && $oldPath !== $generatedPath) {
                    Storage::disk('local')->delete($oldPath);
                }

                return 'Draft surat berhasil diperbarui.';
            }

            $attributes['created_by'] = $userId;
            $attributes['status'] = OutgoingLetterStatus::DRAFT;
            $this->letters->create($attributes, $userId);

            return 'Draft surat berhasil dibuat.';
        } catch (\Throwable $exception) {
            report($exception);
            Storage::disk('local')->delete($generatedPath);
            throw $exception;
        }
    }

    private function extractText(string $path): string
    {
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            throw new \RuntimeException('File DOCX tidak dapat dibuka.');
        }

        $xml = $zip->getFromName('word/document.xml') ?: '';
        $zip->close();

        $dom = new DOMDocument();
        $dom->preserveWhiteSpace = true;
        if (! $dom->loadXML($xml, LIBXML_NOBLANKS | LIBXML_NOERROR | LIBXML_NOWARNING)) {
            return '';
        }

        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
        $paragraphs = $xpath->query('//w:p');
        $parts = [];

        if ($paragraphs) {
            foreach ($paragraphs as $paragraph) {
                $nodes = $xpath->query('.//w:t', $paragraph);
                $line = '';
                if ($nodes) {
                    foreach ($nodes as $node) {
                        $line .= $node->textContent;
                    }
                }
                if ($line !== '') {
                    $parts[] = $line;
                }
            }
        }

        return trim(preg_replace("/\n{3,}/", "\n\n", implode("\n", $parts)) ?? implode("\n", $parts));
    }
}