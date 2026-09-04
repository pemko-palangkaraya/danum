<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

use App\Models\OutgoingLetter;
use App\Services\OutgoingLetterService;
use Livewire\Attributes\On;

trait HandlesOutgoingLetterWorkflow
{
    public function submitLetter(string $id, OutgoingLetterService $service): void
    {
        $this->runWorkflowAction($id, 'submit', fn (OutgoingLetter $letter) => $service->submit($letter, auth()->id()), 'Surat dikirim untuk verifikasi. Data sekarang terkunci.', 'Surat gagal dikirim untuk verifikasi.');
    }

    public function openReject(string $id): void
    {
        try {
            $letter = $this->tenantQuery()->findOrFail($id);
            $this->authorize('reject', $letter);
            $this->rejectId = $id;
            $this->rejectReason = '';
            $this->resetValidation();
            $this->showRejectForm = true;
        } catch (\Throwable) {
            $this->toastError('Anda tidak memiliki kewenangan untuk menolak surat ini.');
        }
    }

    public function rejectLetter(OutgoingLetterService $service): void
    {
        try {
            $this->validate(['rejectReason' => ['required', 'string', 'max:2000']]);
            $letter = $this->tenantQuery()->findOrFail($this->rejectId);
            $this->authorize('reject', $letter);
            $service->reject($letter, auth()->id(), $this->rejectReason);
            $this->showRejectForm = false;
            $this->rejectId = '';
            $this->rejectReason = '';
            $this->dispatch('toast', type: 'success', message: 'Surat ditolak dan dikembalikan kepada pembuat beserta alasan penolakan.');
        } catch (\Illuminate\Validation\ValidationException $exception) {
            $this->setErrorBag($exception->validator->errors());
        } catch (\Throwable $exception) {
            $this->toastError($this->workflowError($exception, 'Surat gagal ditolak.'));
        }
    }

    public function validateLetter(string $id, OutgoingLetterService $service, ?string $note = null): void
    {
        if (blank($note)) {
            $this->requestWorkflowNote('validate', $id, 'Catatan Verifikasi', 'Berikan catatan pemeriksaan sebelum mengesahkan bahwa surat ini sudah diverifikasi.');
            return;
        }

        $this->runWorkflowAction($id, 'validate', fn (OutgoingLetter $letter) => $service->validate($letter, auth()->id(), $note), 'Surat berhasil diverifikasi.', 'Surat gagal diverifikasi. Anda mungkin tidak memiliki kewenangan atau status surat sudah berubah.');
    }

    public function issue(string $id, OutgoingLetterService $service, ?string $note = null, ?string $pin = null): void
    {
        if (! app(\App\Services\SignerPinService::class)->hasPin(auth()->user())) {
            $this->dispatch('signing-pin-missing', url: route('settings.signing-pin'));
            return;
        }

        if (blank($note)) {
            $this->requestWorkflowNote('issue', $id, 'Catatan Penandatanganan', 'Berikan catatan sebelum menandatangani dan menerbitkan surat.');
            return;
        }

        if (blank($pin)) {
            $this->requestSignerPin($id, $note);
            return;
        }

        $this->runWorkflowAction($id, 'issue', fn (OutgoingLetter $letter) => $service->issue($letter, auth()->id(), $note, $pin), 'Surat berhasil diterbitkan.', 'Surat gagal diterbitkan. Silakan cek log aplikasi.', true);
    }

    #[On('workflow-note-submitted')]
    public function handleWorkflowNote(string $action, string $id, string $note, OutgoingLetterService $service): void
    {
        $note = trim($note);
        if ($note === '') {
            $this->requestWorkflowNote($action, $id, $action === 'validate' ? 'Catatan Verifikasi' : 'Catatan Penandatanganan', 'Catatan wajib diisi.');
            return;
        }

        if ($action === 'validate') {
            $this->validateLetter($id, $service, $note);
        } elseif ($action === 'issue') {
            $this->requestSignerPin($id, $note);
        }
    }

    #[On('signer-pin-submitted')]
    public function handleSignerPin(string $action, string $id, string $note, string $pin, OutgoingLetterService $service): void
    {
        if ($action !== 'issue') {
            return;
        }

        $pin = trim($pin);
        if (! preg_match('/^\d{6}$/', $pin)) {
            $this->dispatch('signer-pin-invalid');
            return;
        }

        $this->issue($id, $service, $note, $pin);
    }

    private function requestWorkflowNote(string $action, string $id, string $title, string $description): void
    {
        $this->dispatch('workflow-note-required', action: $action, id: $id, title: $title, description: $description);
    }

    private function requestSignerPin(string $id, string $note): void
    {
        $this->dispatch('signer-pin-required', action: 'issue', id: $id, note: $note, title: 'PIN Tanda Tangan', description: 'Masukkan PIN tanda tangan Anda untuk melanjutkan proses penandatanganan.');
    }

    private function runWorkflowAction(string $id, string $ability, callable $action, string $success, string $fallback, bool $report = false): void
    {
        try {
            $letter = $this->tenantQuery()->findOrFail($id);
            $this->authorize($ability, $letter);
            $action($letter);
            $this->dispatch('toast', type: 'success', message: $success);
        } catch (\Throwable $exception) {
            if ($report) {
                report($exception);
            }
            $this->toastError($this->workflowError($exception, $fallback));
        }
    }

    private function workflowError(\Throwable $exception, string $fallback): string
    {
        return $exception instanceof \DomainException ? $exception->getMessage() : $fallback;
    }
}
