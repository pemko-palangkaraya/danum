<section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
    <div class="border-b border-slate-200 px-5 py-4 sm:px-6">
        <h2 class="text-sm font-semibold text-slate-900">Identitas</h2>
    </div>

    <div class="grid gap-5 p-5 sm:grid-cols-2 sm:p-6">
        @foreach ($identityFields as $field)
            <div @class(['sm:col-span-2' => $field['wide'] ?? false])>
                <label class="block text-sm font-medium text-slate-700">{{ $field['label'] }}</label>
                <input
                    type="{{ $field['type'] ?? 'text' }}"
                    value="{{ $field['value'] }}"
                    disabled
                    class="mt-2 block w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-sm text-slate-700">
            </div>
        @endforeach
    </div>
</section>
