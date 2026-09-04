@if($showSignerPin)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 p-4" wire:click.self="closeSignerPin">
        <x-ui.modal title="PIN Tanda Tangan" size="sm">
            <p class="text-sm text-slate-500">Atur PIN tanda tangan untuk {{ $signerPinUserName }}.</p>

            <div class="mt-5 space-y-4">
                <x-ui.input wire:model="signerPin" label="PIN" id="signer-pin" type="password" inputmode="numeric" maxlength="6" error="{{ $errors->first('signerPin') }}" required />
                <x-ui.input wire:model="signerPinConfirmation" label="Konfirmasi PIN" id="signer-pin-confirmation" type="password" inputmode="numeric" maxlength="6" error="{{ $errors->first('signerPinConfirmation') }}" required />
            </div>

            <x-slot:footer>
                <x-ui.form-actions class="pt-0">
                    <x-ui.button wire:click="closeSignerPin" variant="secondary">Cancel</x-ui.button>
                    <x-ui.button wire:click="saveSignerPin" variant="primary" loading="saveSignerPin">Save PIN</x-ui.button>
                </x-ui.form-actions>
            </x-slot:footer>
        </x-ui.modal>
    </div>
@endif
