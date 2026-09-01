<section id="statistik-penduduk" class="public-stats">
    <div class="public-stats-head">
        <div>
            <span class="public-stats-eyebrow">Data agregat</span>
            <h2>Statistik Kependudukan</h2>
            <p>Lihat ringkasan kependudukan berdasarkan wilayah yang dipilih. Data ditampilkan secara agregat tanpa informasi pribadi.</p>
        </div>
        <div class="public-stats-live"><span></span> Data publik</div>
    </div>

    <div class="public-stats-filters">
        <label><span>Provinsi</span><select wire:model.live="province"><option value="">Semua provinsi</option>@foreach($provinces as $item)<option value="{{ $item }}">{{ $item }}</option>@endforeach</select></label>
        <label><span>Kabupaten / Kota</span><select wire:model.live="city" @disabled(!$province)><option value="">Semua kabupaten/kota</option>@foreach($cities as $item)<option value="{{ $item }}">{{ $item }}</option>@endforeach</select></label>
        <label><span>Kecamatan</span><select wire:model.live="district" @disabled(!$city)><option value="">Semua kecamatan</option>@foreach($districts as $item)<option value="{{ $item }}">{{ $item }}</option>@endforeach</select></label>
        <label><span>Desa / Kelurahan</span><select wire:model.live="village" @disabled(!$district)><option value="">Semua desa/kelurahan</option>@foreach($villages as $item)<option value="{{ $item }}">{{ $item }}</option>@endforeach</select></label>
    </div>

    <div class="public-stats-result" wire:loading.class="opacity-60">
        <div class="public-stat-card blue"><span>Total Penduduk</span><strong>{{ number_format($totalCitizens) }}</strong><small>Warga terdata</small></div>
        <div class="public-stat-card violet"><span>Total KK</span><strong>{{ number_format($totalFamilies) }}</strong><small>Kartu keluarga</small></div>
        <div class="public-stat-card sky"><span>Laki-laki</span><strong>{{ number_format($male) }}</strong><small>{{ $totalCitizens ? number_format(($male / $totalCitizens) * 100, 1) : 0 }}% dari penduduk</small></div>
        <div class="public-stat-card rose"><span>Perempuan</span><strong>{{ number_format($female) }}</strong><small>{{ $totalCitizens ? number_format(($female / $totalCitizens) * 100, 1) : 0 }}% dari penduduk</small></div>
    </div>

    <div class="public-stats-note">Menampilkan agregat dari data tenant pada wilayah yang dipilih.</div>
</section>

@once
<style>
    .public-stats { max-width:1180px; margin:20px auto 0; padding:54px 28px 74px; }
    .public-stats-head { display:flex; justify-content:space-between; gap:30px; align-items:flex-end; margin-bottom:25px; }
    .public-stats-eyebrow { display:inline-flex; padding:6px 10px; border-radius:999px; background:#eef2ff; color:#4f46e5; font-size:10px; font-weight:800; letter-spacing:.08em; text-transform:uppercase; }
    .public-stats-head h2 { margin:11px 0 7px; font-size:30px; line-height:1.1; letter-spacing:-1px; font-weight:850; color:#0f172a; }
    .public-stats-head p { max-width:650px; margin:0; color:#64748b; font-size:13px; line-height:1.7; }
    .public-stats-live { display:inline-flex; align-items:center; gap:7px; padding:8px 11px; border:1px solid #dbe3ef; border-radius:999px; background:#fff; color:#64748b; font-size:10px; font-weight:700; white-space:nowrap; box-shadow:0 3px 12px rgba(15,23,42,.04); }
    .public-stats-live span { width:7px; height:7px; border-radius:50%; background:#22c55e; box-shadow:0 0 0 4px rgba(34,197,94,.10); }
    .public-stats-filters { display:grid; grid-template-columns:repeat(4,1fr); gap:12px; padding:16px; border:1px solid #e1e7f0; border-radius:18px; background:#fff; box-shadow:0 10px 30px rgba(15,23,42,.05); }
    .public-stats-filters label { display:block; }
    .public-stats-filters label span { display:block; margin:0 0 6px 2px; color:#64748b; font-size:10px; font-weight:800; text-transform:uppercase; letter-spacing:.05em; }
    .public-stats-filters select { width:100%; height:42px; padding:0 12px; border:1px solid #dbe3ef; border-radius:10px; background:#f8fafc; color:#334155; font-size:12px; outline:none; }
    .public-stats-filters select:focus { border-color:#818cf8; box-shadow:0 0 0 3px rgba(99,102,241,.10); background:#fff; }
    .public-stats-filters select:disabled { cursor:not-allowed; opacity:.55; }
    .public-stats-result { display:grid; grid-template-columns:repeat(4,1fr); gap:14px; margin-top:16px; transition:opacity .15s ease; }
    .public-stat-card { position:relative; overflow:hidden; min-height:130px; padding:20px; border:1px solid; border-radius:17px; background:#fff; box-shadow:0 7px 22px rgba(15,23,42,.05); }
    .public-stat-card::after { content:""; position:absolute; right:-28px; top:-35px; width:100px; height:100px; border-radius:50%; background:currentColor; opacity:.055; }
    .public-stat-card span { display:block; font-size:11px; font-weight:800; text-transform:uppercase; letter-spacing:.06em; }
    .public-stat-card strong { display:block; margin-top:9px; color:#0f172a; font-size:31px; line-height:1; font-weight:850; letter-spacing:-1px; }
    .public-stat-card small { display:block; margin-top:8px; color:#94a3b8; font-size:10px; }
    .public-stat-card.blue { color:#2563eb; border-color:#bfdbfe; } .public-stat-card.violet { color:#7c3aed; border-color:#ddd6fe; } .public-stat-card.sky { color:#0284c7; border-color:#bae6fd; } .public-stat-card.rose { color:#e11d48; border-color:#fecdd3; }
    .public-stats-note { margin-top:13px; color:#94a3b8; font-size:10px; text-align:right; }
    @media (max-width:820px) { .public-stats { padding:35px 18px 55px; } .public-stats-head { align-items:flex-start; flex-direction:column; } .public-stats-filters,.public-stats-result { grid-template-columns:1fr 1fr; } }
    @media (max-width:520px) { .public-stats-filters,.public-stats-result { grid-template-columns:1fr; } }
</style>
@endonce
