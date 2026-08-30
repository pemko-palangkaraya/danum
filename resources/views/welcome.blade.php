<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>DANUM — Administrasi Persuratan Digital</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        :root { color-scheme: light; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; background: #f7f9fc; color: #0f172a; }
        .landing { min-height: 100vh; overflow: hidden; }
        .nav { height: 72px; display: flex; align-items: center; justify-content: space-between; max-width: 1180px; margin: 0 auto; padding: 0 28px; }
        .logo { font-size: 31px; line-height: 1; font-weight: 900; letter-spacing: -1.8px; color: #fbbd00; }
        .nav-actions { display: flex; gap: 10px; align-items: center; }
        .btn { display: inline-flex; align-items: center; justify-content: center; min-height: 42px; padding: 0 18px; border-radius: 11px; text-decoration: none; font-size: 13px; font-weight: 700; transition: .18s ease; }
        .btn-primary { background: #0f172a; color: #fff; box-shadow: 0 5px 14px rgba(15,23,42,.14); }
        .btn-primary:hover { transform: translateY(-1px); background: #182238; }
        .btn-secondary { background: #fff; color: #334155; border: 1px solid #dbe3ef; }
        .hero { max-width: 1180px; margin: 0 auto; padding: 76px 28px 45px; display: grid; grid-template-columns: 1.08fr .92fr; gap: 70px; align-items: center; }
        .eyebrow { display: inline-flex; align-items: center; gap: 8px; padding: 7px 11px; border-radius: 999px; background: #fff; border: 1px solid #e3e9f2; color: #64748b; font-size: 11px; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; }
        .dot { width: 7px; height: 7px; border-radius: 50%; background: #22c55e; }
        h1 { margin: 20px 0 18px; max-width: 700px; font-size: clamp(40px, 5.4vw, 68px); line-height: 1.02; letter-spacing: -3px; font-weight: 850; }
        h1 span { color: #eab308; }
        .lead { max-width: 620px; margin: 0; color: #64748b; font-size: 17px; line-height: 1.75; }
        .hero-actions { display: flex; flex-wrap: wrap; gap: 11px; margin-top: 28px; }
        .trust { margin-top: 26px; color: #94a3b8; font-size: 12px; }
        .preview-card { position: relative; background: #fff; border: 1px solid #e1e7f0; border-radius: 22px; padding: 18px; box-shadow: 0 22px 55px rgba(15,23,42,.10); transform: rotate(1deg); }
        .preview-head { display: flex; justify-content: space-between; align-items: center; padding: 4px 3px 17px; border-bottom: 1px solid #edf1f6; }
        .preview-title { font-size: 14px; font-weight: 800; }
        .pill { font-size: 10px; font-weight: 800; color: #047857; background: #ecfdf5; padding: 6px 9px; border-radius: 999px; }
        .metric-grid { display: grid; grid-template-columns: repeat(3,1fr); gap: 9px; margin-top: 14px; }
        .metric { padding: 13px; background: #f8fafc; border: 1px solid #edf1f5; border-radius: 13px; }
        .metric small { color: #94a3b8; font-size: 10px; }
        .metric strong { display: block; margin-top: 6px; font-size: 21px; }
        .workflow { margin-top: 12px; padding: 14px; border: 1px solid #edf1f5; border-radius: 14px; }
        .workflow-label { display: flex; justify-content: space-between; gap: 12px; color: #64748b; font-size: 11px; margin-bottom: 11px; }
        .steps { display: grid; grid-template-columns: repeat(4,1fr); gap: 6px; }
        .step { height: 8px; border-radius: 999px; background: #e2e8f0; }
        .step.active { background: #0f172a; }
        .features { max-width: 1180px; margin: 22px auto 0; padding: 20px 28px 82px; display: grid; grid-template-columns: repeat(3,1fr); gap: 16px; }
        .feature { background: #fff; border: 1px solid #e1e7f0; border-radius: 17px; padding: 23px; }
        .feature-icon { width: 38px; height: 38px; display: grid; place-items: center; border-radius: 11px; background: #fff8db; color: #a16207; font-weight: 900; font-size: 11px; }
        .feature h2 { margin: 15px 0 7px; font-size: 16px; }
        .feature p { margin: 0; color: #718096; font-size: 13px; line-height: 1.7; }
        .footer { border-top: 1px solid #e5eaf1; color: #94a3b8; font-size: 11px; }
        .footer-inner { max-width: 1180px; margin: auto; padding: 20px 28px; display: flex; justify-content: space-between; gap: 16px; }
        @media (max-width: 820px) {
            .nav { padding: 0 18px; }
            .nav .btn-secondary { display: none; }
            .hero { grid-template-columns: 1fr; gap: 38px; padding: 55px 18px 25px; }
            h1 { letter-spacing: -2px; }
            .preview-card { transform: none; }
            .features { grid-template-columns: 1fr; padding: 20px 18px 55px; }
            .footer-inner { padding: 18px; flex-direction: column; }
        }
    </style>
</head>
<body>
<div class="landing">
    <header class="nav">
        <div class="logo">DANUM</div>
        <div class="nav-actions">
            @auth
                <a class="btn btn-secondary" href="{{ route('dashboard') }}">Dashboard</a>
                <a class="btn btn-primary" href="{{ route('dashboard') }}">Masuk ke Aplikasi</a>
            @else
                <a class="btn btn-primary" href="{{ route('login') }}">Masuk</a>
            @endauth
        </div>
    </header>

    <main>
        <section class="hero">
            <div>
                <div class="eyebrow"><span class="dot"></span> Administrasi persuratan digital</div>
                <h1>Kelola surat secara <span>lebih sederhana.</span></h1>
                <p class="lead">DANUM membantu organisasi mengelola surat keluar dari penyusunan, verifikasi, hingga tanda tangan elektronik dalam satu alur kerja yang terukur.</p>
                <div class="hero-actions">
                    @auth
                        <a class="btn btn-primary" href="{{ route('dashboard') }}">Buka Dashboard →</a>
                    @else
                        <a class="btn btn-primary" href="{{ route('login') }}">Masuk ke DANUM →</a>
                    @endauth
                    <a class="btn btn-secondary" href="#fitur">Lihat fitur</a>
                </div>
                <div class="trust">Terpusat • Terstruktur • Terdokumentasi</div>
            </div>

            <div class="preview-card" aria-hidden="true">
                <div class="preview-head"><span class="preview-title">Pusat kendali DANUM</span><span class="pill">Data live</span></div>
                <div class="metric-grid">
                    <div class="metric"><small>Surat</small><strong>24</strong></div>
                    <div class="metric"><small>Verifikasi</small><strong>6</strong></div>
                    <div class="metric"><small>Terbit</small><strong>18</strong></div>
                </div>
                <div class="workflow">
                    <div class="workflow-label"><span>Status workflow</span><span>Draft → Verifikasi → TTE → Terbit</span></div>
                    <div class="steps"><span class="step active"></span><span class="step active"></span><span class="step active"></span><span class="step"></span></div>
                </div>
            </div>
        </section>

        <section id="fitur" class="features">
            <article class="feature"><div class="feature-icon">01</div><h2>Alur surat terstruktur</h2><p>Surat bergerak melalui tahapan draft, verifikasi, siap TTE, dan terbit dengan status yang jelas.</p></article>
            <article class="feature"><div class="feature-icon">02</div><h2>Tanda tangan elektronik</h2><p>Dokumen dapat diterbitkan melalui proses TTE dengan sertifikat dan PIN penanda tangan.</p></article>
            <article class="feature"><div class="feature-icon">03</div><h2>Jejak administrasi</h2><p>Aktivitas dan perubahan penting tercatat sehingga proses persuratan lebih mudah ditelusuri.</p></article>
        </section>
    </main>

    <footer class="footer"><div class="footer-inner"><span>© {{ date('Y') }} DANUM</span><span>Platform administrasi persuratan digital</span></div></footer>
</div>
</body>
</html>
