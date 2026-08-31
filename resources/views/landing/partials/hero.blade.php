<section class="hero">
    <div>
        <div class="eyebrow"><span class="dot"></span> Administrasi Digital</div>
        <h1>Kelola data <span>lebih sederhana.</span></h1>
        <p class="lead">DANUM — Sistem administrasi persuratan digital yang membantu organisasi mengelola data, alur surat, verifikasi, dan tanda tangan elektronik dalam satu tempat.</p>
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

    <div class="preview-wrap">
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
    </div>
</section>