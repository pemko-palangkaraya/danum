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
        <section class="w-full max-w-lg rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <p class="text-sm font-medium text-slate-500">Kependudukan</p>
            <h1 class="mt-1 text-xl font-semibold">Membuat PDF Semua Kartu Keluarga</h1>
            <p class="mt-2 text-sm text-slate-600">
                {{ $tenant->name }} sedang diproses. Proses tetap berjalan di server meskipun halaman ditutup.
            </p>

            <div id="export-status" class="mt-6 rounded-xl bg-slate-50 p-4">
                <div id="state-processing" class="flex items-center gap-3">
                    <span class="h-5 w-5 shrink-0 animate-spin rounded-full border-2 border-slate-300 border-t-slate-900"></span>
                    <div>
                        <p id="processing-title" class="text-sm font-semibold">Menunggu worker memulai proses...</p>
                        <p id="processing-description" class="mt-1 text-xs text-slate-500">
                            Pekerjaan sudah masuk antrean dan akan diproses worker.
                        </p>
                    </div>
                </div>

                <div id="state-ready" class="hidden space-y-3">
                    <div>
                        <p class="text-sm font-semibold text-emerald-700">PDF siap dibuka.</p>
                        <p class="mt-1 text-xs text-slate-500">File PDF sudah tersedia di server.</p>
                    </div>
                    <a
                        id="download-link"
                        href="#"
                        class="inline-flex cursor-pointer rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800"
                    >
                        Buka / Download PDF
                    </a>
                </div>

                <div id="state-failed" class="hidden text-sm text-red-700">
                    <p class="font-semibold">Pembuatan PDF gagal.</p>
                    <p id="error-message" class="mt-1"></p>
                </div>

                <div id="state-error" class="hidden text-sm text-red-700">
                    <p class="font-semibold">Gagal memeriksa status PDF.</p>
                    <p id="status-error-message" class="mt-1"></p>
                </div>
            </div>

            <div class="mt-5 flex justify-end">
                <button
                    id="close-page"
                    type="button"
                    class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800"
                >
                    Tutup Halaman
                </button>
            </div>
        </section>
    </main>

    <script>
        (() => {
            const statusUrl = @json(route('population.families.pdf.all.status', ['id' => $export->id]));
            const familyIndexUrl = @json(route('population.families.index'));

            const processing = document.getElementById('state-processing');
            const ready = document.getElementById('state-ready');
            const failed = document.getElementById('state-failed');
            const errorState = document.getElementById('state-error');
            const processingTitle = document.getElementById('processing-title');
            const processingDescription = document.getElementById('processing-description');
            const downloadLink = document.getElementById('download-link');
            const errorMessage = document.getElementById('error-message');
            const statusErrorMessage = document.getElementById('status-error-message');
            const closeButton = document.getElementById('close-page');

            let timer = null;
            let checking = false;

            const stopPolling = () => {
                if (timer !== null) {
                    window.clearInterval(timer);
                    timer = null;
                }
            };

            const showState = (state) => {
                processing.classList.toggle('hidden', state !== 'processing');
                ready.classList.toggle('hidden', state !== 'ready');
                failed.classList.toggle('hidden', state !== 'failed');
                errorState.classList.toggle('hidden', state !== 'error');
            };

            const checkStatus = async () => {
                if (checking) {
                    return;
                }

                checking = true;

                try {
                    const response = await fetch(statusUrl, {
                        headers: {
                            Accept: 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        cache: 'no-store',
                    });

                    if (!response.ok) {
                        throw new Error(`Status HTTP ${response.status}`);
                    }

                    const data = await response.json();

                    if (data.status === 'failed') {
                        stopPolling();
                        errorMessage.textContent = data.error || 'Silakan coba lagi.';
                        showState('failed');
                        return;
                    }

                    if (data.ready === true && data.download_url) {
                        stopPolling();
                        downloadLink.href = data.download_url;
                        showState('ready');
                        return;
                    }

                    showState('processing');

                    if (data.status === 'processing') {
                        processingTitle.textContent = 'Worker sedang membuat PDF...';
                        processingDescription.textContent = 'PDF masih diproses. Tunggu sampai file benar-benar tersedia.';
                    } else if (data.status === 'completed') {
                        processingTitle.textContent = 'PDF sudah dibuat, menyiapkan file...';
                        processingDescription.textContent = 'Server sedang memastikan file PDF sudah tersedia untuk diunduh.';
                    } else {
                        processingTitle.textContent = 'Menunggu worker memulai proses...';
                        processingDescription.textContent = 'Pekerjaan sudah masuk antrean dan akan diproses worker.';
                    }
                } catch (exception) {
                    errorState.classList.remove('hidden');
                    statusErrorMessage.textContent = exception instanceof Error
                        ? exception.message
                        : 'Tidak dapat memeriksa status pembuatan PDF.';
                } finally {
                    checking = false;
                }
            };

            closeButton.addEventListener('click', () => {
                stopPolling();
                window.close();

                window.setTimeout(() => {
                    if (!window.closed) {
                        window.location.replace(familyIndexUrl);
                    }
                }, 250);
            });

            checkStatus();
            timer = window.setInterval(checkStatus, 2000);
        })();
    </script>
</body>
</html>
