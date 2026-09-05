<?php

declare(strict_types=1);

namespace App\Livewire\Settings;

use App\Services\UserPasswordService;
use Illuminate\Support\Facades\Validator;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Password extends Component
{
    public string $currentPassword = '';
    public string $newPassword = '';
    public string $newPasswordConfirmation = '';

    public function save(UserPasswordService $passwordService): void
    {
        $validated = Validator::make(
            [
                'currentPassword' => $this->currentPassword,
                'newPassword' => $this->newPassword,
                'newPasswordConfirmation' => $this->newPasswordConfirmation,
            ],
            [
                'currentPassword' => ['required', 'string'],
                'newPassword' => ['required', 'string', 'min:8', 'different:currentPassword'],
                'newPasswordConfirmation' => ['required', 'same:newPassword'],
            ],
            [
                'currentPassword.required' => 'Password saat ini wajib diisi.',
                'newPassword.required' => 'Password baru wajib diisi.',
                'newPassword.min' => 'Password baru minimal 8 karakter.',
                'newPassword.different' => 'Password baru harus berbeda dari password saat ini.',
                'newPasswordConfirmation.required' => 'Konfirmasi password wajib diisi.',
                'newPasswordConfirmation.same' => 'Konfirmasi password tidak sama.',
            ],
        )->validate();

        $passwordService->change(
            auth()->user(),
            $validated['currentPassword'],
            $validated['newPassword'],
        );

        $this->resetForm();
        session()->flash('toast', ['type' => 'success', 'message' => 'Password berhasil diubah.']);
    }

    public function resetForm(): void
    {
        $this->currentPassword = '';
        $this->newPassword = '';
        $this->newPasswordConfirmation = '';
        $this->resetValidation();
    }

    public function render()
    {
        return view('livewire.pages.settings.password');
    }
}
