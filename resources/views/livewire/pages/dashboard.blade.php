<div>
    @php
        $user = auth()->user();
        $isSuperAdmin = $user->isSuperAdmin();
        $tenant = $user->tenant;
        $tenantName = $tenant?->name ?? 'Seluruh Tenant';
    @endphp

    <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-sm font-medium text-slate-400">{{ $isSuperAdmin ? 'Platform Administration' : 'Workspace' }}</p>
            <h1 class="mt-1 text-3xl font-semibold tracking-tight text-slate-900">Dashboard</h1>
            <p class="mt-2 text-sm text-slate-500">Selamat datang, {{ $user->name }}. Berikut ringkasan aktivitas {{ $isSuperAdmin ? 'DANUM' : 'organisasi Anda' }}.</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
            <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">{{ $isSuperAdmin ? 'Scope' : 'Organisasi' }}</p>
            <p class="mt-1 text-sm font-semibold text-slate-800">{{ $tenantName }}</p>
        </div>
    </div>

    <div class="overflow-hidden rounded-3xl bg-slate-900 shadow-xl">
        <div class="p-6 sm:p-8">
            <div class="flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
                <div class="max-w-2xl">
                    <div class="inline-flex items-center gap-2 rounded-full bg-white/10 px-3 py-1 text-xs font-semibold text-slate-200">
                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-400"></span>
                        Sistem aktif
                    </div>
                    <h2 class="mt-4 text-2xl font-semibold tracking-tight text-white sm:text-3xl">Pusat kendali administrasi surat digital.</h2>
                    <p class="mt-3 text-sm leading-6 text-slate-300">Kelola alur surat, verifikasi, penandatanganan, jabatan, dan akses dari satu tempat.</p>
                </div>
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-4 lg:w-[420px]">
                    <div class="rounded-2xl bg-white/10 p-4"><p class="text-2xl font-semibold text-white">—</p><p class="mt-1 text-[11px] text-slate-400">Surat</p></div>
                    <div class="rounded-2xl bg-white/10 p-4"><p class="text-2xl font-semibold text-white">—</p><p class="mt-1 text-[11px] text-slate-400">Draft</p></div>
                    <div class="rounded-2xl bg-white/10 p-4"><p class="text-2xl font-semibold text-white">—</p><p class="mt-1 text-[11px] text-slate-400">Terbit</p></div>
                    <div class="rounded-2xl bg-white/10 p-4"><p class="text-2xl font-semibold text-white">—</p><p class="mt-1 text-[11px] text-slate-400">Aktif</p></div>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @php
            $cards = $isSuperAdmin
                ? [['label'=>'Organisasi','value'=>'—','hint'=>'Tenant terdaftar','icon'=>'building'],['label'=>'Pengguna','value'=>'—','hint'=>'Seluruh platform','icon'=>'users'],['label'=>'Surat Terbit','value'=>'—','hint'=>'Seluruh tenant','icon'=>'document'],['label'=>'Aktivitas','value'=>'—','hint'=>'Audit terbaru','icon'=>'activity']]
                : [['label'=>'Surat Saya','value'=>'—','hint'=>'Dokumen yang dibuat','icon'=>'document'],['label'=>'Perlu Verifikasi','value'=>'—','hint'=>'Menunggu tindakan','icon'=>'check'],['label'=>'Sudah Terbit','value'=>'—','hint'=>'Dokumen resmi','icon'=>'archive'],['label'=>'Anggota','value'=>'—','hint'=>'Dalam organisasi','icon'=>'users']];
        @endphp
        @foreach($cards as $card)
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-start justify-between gap-3"><div><p class="text-sm font-medium text-slate-500">{{ $card['label'] }}</p><p class="mt-2 text-3xl font-semibold tracking-tight text-slate-900">{{ $card['value'] }}</p></div><div class="flex h-10 w-10 items-center justify-center rounded-xl bg-slate-100 text-slate-500"><span class="text-xs font-bold">{{ strtoupper(substr($card['icon'],0,2)) }}</span></div></div>
                <p class="mt-3 text-xs text-slate-400">{{ $card['hint'] }}</p>
            </div>
        @endforeach
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-3">
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm lg:col-span-2">
            <div class="flex items-center justify-between"><div><h2 class="font-semibold text-slate-900">Alur kerja</h2><p class="mt-1 text-xs text-slate-500">Gambaran proses surat di {{ $isSuperAdmin ? 'platform' : 'organisasi ini' }}.</p></div><span class="rounded-full bg-slate-100 px-3 py-1 text-[11px] font-semibold text-slate-500">Live</span></div>
            <div class="mt-6 grid gap-3 sm:grid-cols-5">
                @foreach(['Draft','Verifikasi','Validasi','Tanda Tangan','Terbit'] as $index => $step)
                    <div class="relative rounded-xl border border-slate-200 p-4"><div class="flex h-8 w-8 items-center justify-center rounded-lg bg-slate-900 text-xs font-bold text-white">{{ $index+1 }}</div><p class="mt-3 text-sm font-semibold text-slate-800">{{ $step }}</p><p class="mt-1 text-[11px] text-slate-400">Pantau status</p></div>
                @endforeach
            </div>
        </section>
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="font-semibold text-slate-900">Akses cepat</h2><p class="mt-1 text-xs text-slate-500">Menu yang tersedia untuk akun Anda.</p>
            <div class="mt-5 space-y-2">
                @can('viewAny', App\Models\OutgoingLetter::class)<a href="{{ route('outgoing-letters.index') }}" class="flex items-center justify-between rounded-xl border border-slate-100 px-4 py-3 text-sm font-medium text-slate-700 hover:bg-slate-50"><span>Surat Keluar</span><span>→</span></a>@endcan
                @if($isSuperAdmin && Route::has('tenants.index'))<a href="{{ route('tenants.index') }}" class="flex items-center justify-between rounded-xl border border-slate-100 px-4 py-3 text-sm font-medium text-slate-700 hover:bg-slate-50"><span>Kelola Organisasi</span><span>→</span></a>@endif
                @if($user->hasPermission('positions.view'))<a href="{{ $isSuperAdmin ? route('positions.admin.index') : route('positions.index') }}" class="flex items-center justify-between rounded-xl border border-slate-100 px-4 py-3 text-sm font-medium text-slate-700 hover:bg-slate-50"><span>Jabatan</span><span>→</span></a>@endif
                @if($user->hasPermission('rbac.view'))<a href="{{ route('rbac.index') }}" class="flex items-center justify-between rounded-xl border border-slate-100 px-4 py-3 text-sm font-medium text-slate-700 hover:bg-slate-50"><span>Role & Akses</span><span>→</span></a>@endif
            </div>
        </section>
    </div>

    <div class="mt-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between"><div><h2 class="font-semibold text-slate-900">Aktivitas terbaru</h2><p class="mt-1 text-xs text-slate-500">Ringkasan aktivitas akan tampil berdasarkan kewenangan akun.</p></div><span class="text-xs font-medium text-slate-400">Belum ada data ringkasan</span></div>
        <div class="mt-5 rounded-xl border border-dashed border-slate-200 p-8 text-center"><p class="text-sm font-medium text-slate-600">Dashboard siap dihubungkan ke metrik real-time.</p><p class="mt-1 text-xs text-slate-400">Struktur UI sudah dipisahkan berdasarkan role dan tenant tanpa membocorkan data lintas organisasi.</p></div>
    </div>
</div>