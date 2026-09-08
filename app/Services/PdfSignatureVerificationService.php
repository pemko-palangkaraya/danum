<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\OutgoingLetter;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

final class PdfSignatureVerificationService
{
    public function verify(OutgoingLetter $letter): array
    {
        $path = $letter->signed_pdf_path;

        if (blank($path)) {
            return $this->result('unsigned', 'Dokumen belum memiliki tanda tangan elektronik.');
        }

        if (! Storage::disk('local')->exists($path)) {
            return $this->result('invalid', 'File PDF bertanda tangan tidak ditemukan.');
        }

        $absolutePath = Storage::disk('local')->path($path);
        $pdf = file_get_contents($absolutePath);
        if ($pdf === false) {
            return $this->result('invalid', 'File PDF bertanda tangan tidak dapat dibaca.');
        }

        if ($letter->document_hash === null || $letter->document_hash_algorithm !== 'SHA-256') {
            return $this->result('invalid', 'Hash dokumen final belum tersedia atau algoritmanya tidak didukung.');
        }

        $actualHash = hash_file('sha256', $absolutePath);
        if ($actualHash === false || ! hash_equals(strtolower($letter->document_hash), strtolower($actualHash))) {
            return $this->result('invalid', 'Hash PDF final tidak cocok dengan hash yang tercatat.');
        }

        try {
            $signature = $this->extractSignature($pdf);
            $verifierClass = '\\Com\\Tecnick\\Pdf\\Sign\\Cms\\SignedDataVerifier';

            if (! class_exists($verifierClass)) {
                throw new RuntimeException('Komponen validator CMS dari tc-lib-pdf-sign tidak tersedia.');
            }

            $certificateDer = (new $verifierClass(
                requireSigningCertificate: true,
            ))->verify($signature['cms'], $signature['content']);

            $certificatePem = "-----BEGIN CERTIFICATE-----\n"
                . chunk_split(base64_encode($certificateDer), 64, "\n")
                . "-----END CERTIFICATE-----\n";
            $certificate = openssl_x509_read($certificatePem);
            if ($certificate === false) {
                throw new RuntimeException('Sertifikat penanda tangan pada CMS tidak dapat dibaca.');
            }

            $fingerprint = strtoupper((string) openssl_x509_fingerprint($certificate, 'sha256'));
            $storedFingerprint = strtoupper(str_replace(':', '', (string) $letter->signerCertificate?->fingerprint_sha256));
            if ($storedFingerprint !== '' && ! hash_equals($storedFingerprint, $fingerprint)) {
                throw new RuntimeException('Sertifikat pada PDF tidak cocok dengan sertifikat penanda tangan yang tercatat di DANUM.');
            }

            return $this->result('valid', 'Tanda tangan PAdES terverifikasi secara kriptografis.', [
                'profile' => str_contains($pdf, '/SubFilter /ETSI.CAdES.detached') ? 'pades' : 'cms',
                'certificate_fingerprint_sha256' => $fingerprint,
            ]);
        } catch (\Throwable $exception) {
            return $this->result('invalid', $exception->getMessage());
        }
    }

    private function extractSignature(string $pdf): array
    {
        if (! preg_match('/\/ByteRange\s*\[\s*(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s*\]/', $pdf, $rangeMatch)) {
            throw new RuntimeException('PDF tidak memiliki ByteRange tanda tangan yang valid.');
        }

        $ranges = array_map('intval', array_slice($rangeMatch, 1));
        [$offset1, $length1, $offset2, $length2] = $ranges;

        if ($offset1 !== 0 || $length1 < 1 || $offset2 <= $length1 || $length2 < 1) {
            throw new RuntimeException('ByteRange tanda tangan tidak valid.');
        }

        $content = substr($pdf, $offset1, $length1) . substr($pdf, $offset2, $length2);
        if (strlen($content) !== $length1 + $length2) {
            throw new RuntimeException('ByteRange PDF tidak lengkap.');
        }

        $contentsPosition = strpos($pdf, '/Contents', $rangeMatch[0] !== '' ? strpos($pdf, $rangeMatch[0]) : 0);
        if ($contentsPosition === false || ! preg_match('/\/Contents\s*<([0-9A-Fa-f\s]+)>/', substr($pdf, $contentsPosition), $contentsMatch)) {
            throw new RuntimeException('CMS tanda tangan PDF tidak ditemukan.');
        }

        $cms = hex2bin(preg_replace('/\s+/', '', $contentsMatch[1]));
        if ($cms === false || strlen($cms) < 2) {
            throw new RuntimeException('CMS tanda tangan PDF tidak valid.');
        }

        $cms = substr($cms, 0, $this->derLength($cms));

        return [
            'content' => $content,
            'cms' => $cms,
        ];
    }

    private function derLength(string $der): int
    {
        if (strlen($der) < 2) {
            throw new RuntimeException('DER signature terlalu pendek.');
        }

        $first = ord($der[1]);
        if (($first & 0x80) === 0) {
            return 2 + $first;
        }

        $lengthBytes = $first & 0x7f;
        if ($lengthBytes < 1 || $lengthBytes > 4 || strlen($der) < 2 + $lengthBytes) {
            throw new RuntimeException('Panjang DER signature tidak valid.');
        }

        $length = 0;
        for ($index = 0; $index < $lengthBytes; $index++) {
            $length = ($length << 8) | ord($der[2 + $index]);
        }

        return 2 + $lengthBytes + $length;
    }

    private function result(string $status, string $message, array $extra = []): array
    {
        return [
            'status' => $status,
            'message' => $message,
            ...$extra,
        ];
    }
}
