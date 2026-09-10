@if ($canUpdate)
    <div class="rounded-xl border border-blue-200 bg-blue-50 px-4 py-3 text-sm font-medium text-blue-800">
        Anda memiliki izin untuk mengubah profil organisasi. Perubahan akan dicatat pada Audit Log.
    </div>
@else
    <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-medium text-amber-800">
        Anda hanya memiliki izin untuk melihat profil organisasi. Hubungi Administrator jika membutuhkan akses untuk mengubah data.
    </div>
@endif
