<x-ui.card>
    <x-slot:header>
        <h2 class="text-sm font-semibold text-slate-900">Initial Administrator</h2>
        <p class="mt-1 text-xs text-slate-500">Akun ini menjadi administrator tenant.</p>
    </x-slot:header>

    <div class="grid gap-5 sm:grid-cols-2">
        <x-ui.input wire:model="admin_name" label="Name" id="admin-name" error="{{ $errors->first('admin_name') }}" required />
        <x-ui.input wire:model="admin_email" label="Email / Login" id="admin-email" type="email" error="{{ $errors->first('admin_email') }}" required />
        <x-ui.input wire:model="admin_password" label="Password" id="admin-password" type="password" autocomplete="new-password" error="{{ $errors->first('admin_password') }}" required />
        <x-ui.input wire:model="admin_password_confirmation" label="Confirm Password" id="admin-password-confirmation" type="password" autocomplete="new-password" error="{{ $errors->first('admin_password_confirmation') }}" required />
    </div>
</x-ui.card>
