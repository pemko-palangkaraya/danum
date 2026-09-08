<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Membuat PDF Kartu Keluarga</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 text-slate-900">
    <main class="flex min-h-screen items-center justify-center px-4 py-10">
        <section
            class="w-full max-w-lg rounded-2xl border border-slate-200 bg-white p-6 shadow-sm"
            x-data="{
                status: 'queued',
                error: '',
                downloadUrl: '',
                timer: null,
                async check() {
                    try {
                        const response = await fetch('{{ route('population.families.pdf.all.status', ['id' => $export->id]) }}', {
                            headers: {
                                Accept: 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            cache: 'no-store',
                        });

                        if (! response.ok) {
                            throw new Error(`Status HTTP ${response.status}`);
                        }

                        const data = await response.json();
                        this.status = data.status ?? 'queued';
                        this.error = data.error ?? '';
                        this.downloadUrl = data.download_url ?? '';

                        if (this.status === 'completed' || this.status === 'failed') {
                            clearInterval(this.timer);
                        }
                    } catch (exception) {
                        this.status = 'error';
                        this.error = exception instanceof Error
                            ? exception.message
                            : 'Tidak dapat memeriksa status pembuatan PDF.';
                    }
                },
                openPdf() {
                    if (! this.downloadUrl) {
                        this.error = 'Link PDF belum tersedia. Silakan tunggu beberapa detik lalu coba lagi.';
                        this.status = 'error';
                        return;
                    }

                    window.open(this.downloadUrl, '_blank', 'noopener');
                }
            }"
            x-init="check(); timer = setInterval(() => check(), 3000)"
        >
            <p class="text-sm font-medium text-slate-500">Kependudukan</p>
            <h1 class="mt-1 text-xl font-semibold">Membuat PDF Semua Kartu Keluarga</h1>
            <p class="mt-2 text-sm text-slate-600">
                {{ $tenant->name }} sedang diproses. Proses tetap berjalan di server meskipun halaman ditutup.
            </p>

            <div class="mt-6 rounded-xl bg-slate-50 p-4">
                <div x-show="status === 'queued' || status === 'processing'" class="flex items-center gap-3">
                    <span class="h-5 w-5 animate-spin rounded-full border-2 border-slate-300 border-t-slate-900"></span>
                    <div>
                        <p class="text-sm font-semibold" x-text="status === 'processing' ? 'Sedang membuat PDF...' : 'Menunggu proses dimulai...'"></p>
                        <p class="mt-1 text-xs text-slate-500">Status diperiksa otomatis setiap beberapa detik.</p>
                    </div>
                </div>

                <div x-show="status === 'completed'" class="space-y-3">
                    <div>
                        <p class="text-sm font-semibold text-emerald-700">PDF selesai dibuat.</p>
                        <p class="mt-1 text-xs text-slate-500">Silakan klik tombol di bawah untuk membuka atau mengunduh PDF.</p>
                    </div>
                    <button
                        type="button"
                        x-on:click="openPdf()"
                        x-bind:disabled="!downloadUrl"
                        class="inline-flex rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        Buka / Download PDF
                    </button>
                </div>

                <div x-show="status === 'failed' || status === 'error'" class="text-sm text-red-700">
                    <p class="font-semibold" x-text="status === 'error' ? 'Gagal memeriksa status PDF.' : 'Pembuatan PDF gagal.'"></p>
                    <p class="mt-1" x-text="error"></p>
                </div>
            </div>

            <div class="mt-5 flex items-center justify-between gap-3">
                <a href="{{ url()->previous() }}" class="text-sm font-semibold text-slate-600 hover:text-slate-900">Kembali</a>
                <a
                    href="{{ route('population.families.index') }}"
                    class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800"
                >
                    Kembali ke Kartu Keluarga
                </a>
            </div>
        </section>
    </main>
</body>
</html>