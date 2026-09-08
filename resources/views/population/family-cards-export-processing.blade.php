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
                queueState: 'queued',
                ready: false,
                error: '',
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
                        this.queueState = data.queue_state ?? 'queued';
                        this.ready = data.ready === true;
                        this.error = data.error ?? '';

                        if (this.ready || this.status === 'failed') {
                            clearInterval(this.timer);
                        }
                    } catch (exception) {
                        this.status = 'error';
                        this.error = exception instanceof Error
                            ? exception.message
                            : 'Tidak dapat memeriksa status pembuatan PDF.';
                    }
                },
                closePage() {
                    // Browser hanya mengizinkan window.close() pada konteks
                    // yang dibuka oleh script/tab opener. Coba beberapa cara
                    // sebelum memberi fallback yang aman.
                    window.open('', '_self');
                    window.close();
                    setTimeout(() => {
                        if (! window.closed) {
                            window.location.replace('{{ route('population.families.index') }}');
                        }
                    }, 300);
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
                        <p class="text-sm font-semibold" x-text="queueState === 'running' ? 'Worker sedang membuat PDF...' : 'Menunggu worker memulai proses...'">
                        </p>
                        <p class="mt-1 text-xs text-slate-500" x-text="queueState === 'running' ? 'PDF masih diproses. Jangan menganggap file sudah selesai sebelum status siap.' : 'Pekerjaan sudah masuk antrean dan akan diproses worker.'"></p>
                    </div>
                </div>

                <div x-show="status === 'completed' && ! ready" class="flex items-center gap-3">
                    <span class="h-5 w-5 animate-spin rounded-full border-2 border-slate-300 border-t-slate-900"></span>
                    <div>
                        <p class="text-sm font-semibold">PDF sudah dibuat, worker sedang menyelesaikan pekerjaan...</p>
                        <p class="mt-1 text-xs text-slate-500">Tombol file akan aktif setelah job worker benar-benar selesai.</p>
                    </div>
                </div>

                <div x-show="ready" class="space-y-3">
                    <div>
                        <p class="text-sm font-semibold text-emerald-700">PDF siap dibuka.</p>
                        <p class="mt-1 text-xs text-slate-500">Job worker sudah selesai dan file tersedia.</p>
                    </div>
                    <a
                        :href="{{ Js::from(route('population.families.pdf.all.download', ['id' => $export->id])) }}"
                        class="inline-flex cursor-pointer rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800"
                    >
                        Buka / Download PDF
                    </a>
                </div>

                <div x-show="status === 'failed' || status === 'error'" class="text-sm text-red-700">
                    <p class="font-semibold" x-text="status === 'error' ? 'Gagal memeriksa status PDF.' : 'Pembuatan PDF gagal.'"></p>
                    <p class="mt-1" x-text="error"></p>
                </div>
            </div>

            <div class="mt-5 flex justify-end">
                <button
                    type="button"
                    @click="closePage()"
                    class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800"
                >
                    Tutup Halaman
                </button>
            </div>
        </section>
    </main>
</body>
</html>
