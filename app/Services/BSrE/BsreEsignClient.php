<?php

declare(strict_types=1);

namespace App\Services\BSrE;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class BsreEsignClient
{
    public function enabled(): bool
    {
        return (bool) config('services.bsre.enabled');
    }

    public function sign(string $pdfPath, string $passphrase, string $signer): string
    {
        if (! $this->enabled()) {
            throw new RuntimeException('Integrasi BSrE belum diaktifkan.');
        }

        if (! is_file($pdfPath) || ! is_readable($pdfPath)) {
            throw new RuntimeException('PDF sumber untuk BSrE tidak ditemukan.');
        }

        $baseUrl = rtrim((string) config('services.bsre.client_url'), '/');
        $endpoint = ltrim((string) config('services.bsre.sign_endpoint'), '/');

        if ($baseUrl === '') {
            throw new RuntimeException('BSRE_ESIGN_CLIENT_URL belum dikonfigurasi.');
        }

        $url = $baseUrl . '/' . $endpoint;
        $request = $this->request();

        $pdfField = (string) config('services.bsre.pdf_field', 'file');
        $passphraseField = (string) config('services.bsre.passphrase_field', 'passphrase');
        $signerField = (string) config('services.bsre.signer_field', 'signer');

        $response = $request
            ->attach($pdfField, fopen($pdfPath, 'r'), basename($pdfPath))
            ->post($url, [
                $passphraseField => $passphrase,
                $signerField => $signer,
            ]);

        if ($response->failed()) {
            throw new RuntimeException(
                'BSrE eSign Client menolak permintaan signing (HTTP ' . $response->status() . ').'
            );
        }

        $contentType = strtolower((string) $response->header('Content-Type'));

        if (str_contains($contentType, 'application/pdf')) {
            return $this->storeResponsePdf($response->body());
        }

        $payload = $response->json();
        if (! is_array($payload)) {
            throw new RuntimeException('Respons BSrE tidak berisi PDF atau JSON yang valid.');
        }

        $base64Field = (string) config('services.bsre.response_base64_field', 'signed_pdf_base64');
        $pdfResponseField = (string) config('services.bsre.response_pdf_field', 'signed_pdf');

        $base64 = data_get($payload, $base64Field);
        if (is_string($base64) && $base64 !== '') {
            $decoded = base64_decode($base64, true);
            if ($decoded !== false && str_starts_with($decoded, '%PDF-')) {
                return $this->storeResponsePdf($decoded);
            }
        }

        $encoded = data_get($payload, $pdfResponseField);
        if (is_string($encoded) && $encoded !== '') {
            $decoded = base64_decode($encoded, true);
            if ($decoded !== false && str_starts_with($decoded, '%PDF-')) {
                return $this->storeResponsePdf($decoded);
            }
        }

        throw new RuntimeException('Respons BSrE belum dapat dipetakan ke PDF bertanda tangan. Periksa kontrak API eSign Client.');
    }

    private function request(): PendingRequest
    {
        $request = Http::timeout((int) config('services.bsre.timeout', 30))
            ->connectTimeout((int) config('services.bsre.timeout', 30))
            ->withOptions([
                'verify' => (bool) config('services.bsre.verify_peer', true),
            ])
            ->acceptJson();

        $token = (string) config('services.bsre.auth_token', '');
        if ($token !== '') {
            $scheme = trim((string) config('services.bsre.auth_scheme', 'Bearer'));
            $value = $scheme !== '' ? $scheme . ' ' . $token : $token;
            $request = $request->withHeaders([
                (string) config('services.bsre.auth_header', 'Authorization') => $value,
            ]);
        }

        return $request;
    }

    private function storeResponsePdf(string $contents): string
    {
        $relativePath = 'outgoing-letters/signed/' . now()->format('Y/m') . '/' . uniqid('bsre-', true) . '.pdf';

        if (! str_starts_with($contents, '%PDF-')) {
            throw new RuntimeException('Respons BSrE bukan file PDF yang valid.');
        }

        if (! \Storage::disk('local')->put($relativePath, $contents)) {
            throw new RuntimeException('PDF hasil BSrE gagal disimpan.');
        }

        return $relativePath;
    }
}
