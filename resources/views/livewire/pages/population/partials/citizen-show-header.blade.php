<x-ui.page-header
    :title="$citizen->nama_lengkap"
    eyebrow="Kependudukan"
    :back-url="route($citizensRoute)"
>
    <x-slot:subtitle>
        <span class="font-mono">NIK {{ $citizen->nik }}</span>
    </x-slot:subtitle>

    <x-slot:actions>
        @if($canManage)
            <a href="{{ route($citizensRoute, ['edit' => $citizen->id]) }}" class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white">
                Edit di Data Warga
            </a>
        @endif
    </x-slot:actions>
</x-ui.page-header>
