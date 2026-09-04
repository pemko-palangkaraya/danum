<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\OutgoingLetterStatus;
use App\Models\LetterType;
use App\Models\OutgoingLetter;
use App\Models\Position;
use App\Models\PositionHolder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final class OutgoingLetterDraftService
{
    public function __construct(
        private readonly OutgoingLetterService $letters,
        private readonly DocxTemplateService $docx,
        private readonly DocxTteService $tte,
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
        $generatedPath = $this->docx->renderToStorage($templatePath, $tenant, $data);

        try {
            $this->tte->embed(
                Storage::disk('local')->path($generatedPath),
                url('/verify/' . $verificationToken),
            );
            $content = $this->docx->extractText(Storage::disk('local')->path($generatedPath));

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
            Storage::disk('local')->delete($generatedPath);
            throw $exception;
        }
    }
}
