<?php

declare(strict_types=1);

namespace App\Services\BSrE;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class BsreEsignClient
{
    public function enabled(): bool
    {
        return (bool) config('services.bsre.enabled');
    }

    /**
     * Sign a PDF through the BSrE eSign Client Service.
     *
     * The application credential is sent using Basic Auth. The signer NIK
     * and passphrase are sent only in the signing request and are never logged.
     */
    public function sign(string $pdfPath, string $nik, string $passphrase, array $options = []): string
    {
        if (! $this->enabled()) {
            throw new RuntimeException('Integrasi BSrE belum diaktifkan.');
        }

        if (! is_file($pdfPath) || ! is_readable($pdfPath)) {
            throw new RuntimeException('PDF sumber untuk BSrE tidak ditemukan.');
        }

        if (! preg_match('/^\d{16}$/', $nik)) {
            throw new RuntimeException('NIK penanda tangan harus terdiri dari 16 digit.');
        }

        $baseUrl = rtrim((string) config('services.bsre.client_url'), '/');
        $endpoint = ltrim((string) config('services.bsre.sign_endpoint'), '/');
        if ($baseUrl === '') {
            throw new RuntimeException('TTE_URL belum dikonfigurasi.');
        }

        $url = $baseUrl . '/' . $endpoint;
        $request = $this->request();

        $pdfField = (string) config('services.bsre.pdf_field', 'file');
        $passphraseField = (string) config('services.bsre.passphrase_field', 'passphrase');
        $nikField = (string) config('services.bsre.nik_field', 'nik');

        $payload = [
            $nikField => $nik,
            $passphraseField => $passphrase,
            (string) config('services.bsre.display_field', 'tampilan') => (string) ($options['tampilan'] ?? config('services.bsre.display_mode', 'visible')),
            (string) config('services.bsre.image_field', 'image') => (string) ($options['image'] ?? config('services.bsre.image_value', 'false')),
            (string) config('services.bsre.page_field', 'page') => (string) ($options['page'] ?? config('services.bsre.page', 1)),
            (string) config('services.bsre.x_field', 'xAxis') => (string) ($options['xAxis'] ?? config('services.bsre.x', 0)),
            (string) config('services.bsre.y_field', 'yAxis') => (string) ($options['yAxis'] ?? config('services.bsre.y', 0)),
            (string) config('services.bsre.width_field', 'width') => (string) ($options['width'] ?? config('services.bsre.width', 0)),
            (string) config('services.bsre.height_field', 'height') => (string) ($options['height'] ?? config('services.bsre.height', 0)),
        ];

        $qrLink = $options['linkQR'] ?? null;
        if (filled($qrLink)) {
            $payload[(string) config('services.bsre.qr_link_field', 'linkQR')] = (string) $qrLink;
        }

        $handle = fopen($pdfPath, 'rb');
        if ($handle === false) {
            throw new RuntimeException('PDF sumber tidak dapat dibuka untuk dikirim ke eSign Client.');
        }

        try {
            $response = $request
                ->attach($pdfField, $handle, basename($pdfPath), ['Content-Type' => 'application/pdf'])
                ->post($url, $payload);
        } finally {
            fclose($handle);
        }

        if ($response->failed()) {
            $detail = trim($response->body());
            $detail = $detail !== '' ? ' Respons: ' . str($detail)->limit(300)->toString() : '';
            throw new RuntimeException(
                'eSign Client menolak permintaan signing (HTTP ' . $response->status() . ').' . $detail
            );
        }

        $contentType = strtolower((string) $response->header('Content-Type'));
        if (str_contains($contentType, 'application/pdf') || str_starts_with($response->body(), '%PDF-')) {
            return $this->storeResponsePdf($response->body());
        }

        $payloadResponse = $response->json();
        if (! is_array($payloadResponse)) {
            throw new RuntimeException('Respons eSign Client tidak berisi PDF atau JSON yang valid.');
        }

        foreach ([
            (string) config('services.bsre.response_base64_field', 'signed_pdf_base64'),
            (string) config('services.bsre.response_pdf_field', 'signed_pdf'),
        ] as $field) {
            $encoded = data_get($payloadResponse, $field);
            if (! is_string($encoded) || $encoded === '') {
                continue;
            }

            $decoded = base64_decode($encoded, true);
            if ($decoded !== false && str_starts_with($decoded, '%PDF-')) {
                return $this->storeResponsePdf($decoded);
            }
        }

        throw new RuntimeException('Respons eSign Client belum dapat dipetakan ke PDF bertanda tangan.');
    }

    private function request(): PendingRequest
    {
        $request = Http::timeout((int) config('services.bsre.timeout', 30))
            ->connectTimeout((int) config('services.bsre.timeout', 30))
            ->withOptions([
                'verify' => (bool) config('services.bsre.verify_peer', true),
            ]);

        if (strtolower((string) config('services.bsre.auth_type', 'basic')) === 'basic') {
            $username = (string) config('services.bsre.username', '');
            $password = (string) config('services.bsre.password', '');

            if ($username === '' || $password === '') {
                throw new RuntimeException('TTE_USERNAME dan TTE_PASSWORD belum dikonfigurasi.');
            }

            return $request->withBasicAuth($username, $password);
        }

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
        if (! str_starts_with($contents, '%PDF-')) {
            throw new RuntimeException('Respons eSign Client bukan file PDF yang valid.');
        }

        $relativePath = 'outgoing-letters/signed/' . now()->format('Y/m') . '/' . uniqid('bsre-', true) . '.pdf';

        if (! Storage::disk('local')->put($relativePath, $contents)) {
            throw new RuntimeException('PDF hasil eSign Client gagal disimpan.');
        }

        return $relativePath;
    }
}
