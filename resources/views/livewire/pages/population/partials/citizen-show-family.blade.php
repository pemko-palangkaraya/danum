<x-ui.card>
    <x-slot:header>
        <h2 class="font-semibold text-slate-900">Keluarga</h2>
    </x-slot:header>

    @if($activeMembership?->family)
        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">No. KK</p>
        <p class="mt-1 font-mono text-lg font-semibold text-slate-900">{{ $activeMembership->family->no_kk }}</p>
        <p class="mt-4 text-xs font-semibold uppercase tracking-wide text-slate-400">Hubungan</p>
        <p class="mt-1 text-sm font-medium text-slate-800">{{ $activeMembership->hubungan_dalam_keluarga }}</p>
        <p class="mt-4 text-xs font-semibold uppercase tracking-wide text-slate-400">Kepala Keluarga</p>
        <p class="mt-1 text-sm font-medium text-slate-800">{{ $activeMembership->family->headCitizen?->nama_lengkap ?: '-' }}</p>
        <a href="{{ route($familiesRoute) }}" class="mt-5 inline-block text-sm font-semibold text-slate-700 hover:text-slate-950">Buka Kartu Keluarga →</a>
    @else
        <p class="text-sm text-slate-500">Warga belum memiliki keanggotaan KK aktif.</p>
    @endif
</x-ui.card>
