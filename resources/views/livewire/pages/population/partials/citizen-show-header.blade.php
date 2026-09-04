<div class="space-y-2">
    <x-ui.page-header
        :title="$citizen->nama_lengkap"
        :description="'NIK '.$citizen->nik"
        :back-url="route($citizensRoute)"
    />

    @if($canManage)
        <div class="flex justify-end">
            <a href="{{ route($citizensRoute, ['edit' => $citizen->id]) }}" class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white">
                Edit di Data Warga
            </a>
        </div>
    @endif
</div>
