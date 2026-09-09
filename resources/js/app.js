import './bootstrap';

const toastHttpErrors = {
    401: 'Sesi login tidak valid. Silakan login kembali.',
    403: 'Anda tidak memiliki izin untuk melakukan tindakan ini.',
    404: 'Data atau halaman yang diminta tidak ditemukan.',
    409: 'Tindakan tidak dapat dilakukan karena data sedang bertentangan dengan kondisi saat ini.',
    419: 'Sesi Anda telah berakhir. Silakan muat ulang halaman lalu coba lagi.',
    422: 'Data yang dikirim tidak valid. Periksa kembali isian Anda.',
    429: 'Terlalu banyak permintaan. Silakan tunggu sebentar lalu coba lagi.',
    500: 'Terjadi kesalahan pada server. Silakan coba lagi.',
    502: 'Server sedang tidak dapat memproses permintaan. Silakan coba lagi.',
    503: 'Layanan sedang tidak tersedia. Silakan coba lagi beberapa saat lagi.',
    504: 'Server terlalu lama merespons. Silakan coba lagi.',
};

const dispatchErrorToast = (message) => {
    window.dispatchEvent(new CustomEvent('toast', {
        detail: {
            type: 'error',
            message,
        },
    }));
};

let shouldFocusValidationError = false;

const focusFirstValidationError = () => {
    const form = document.querySelector('form[wire\\:submit="save"]');
    const errorMessage = form?.querySelector('.text-red-600:not([hidden])');

    if (!errorMessage) {
        return;
    }

    const field = errorMessage.closest('div');
    const target = field?.querySelector(
        'input:not([type="hidden"]), select, textarea, button',
    );

    if (!target || target.disabled || target.readOnly) {
        return;
    }

    window.requestAnimationFrame(() => {
        target.focus({ preventScroll: true });
        target.scrollIntoView({ behavior: 'smooth', block: 'center' });
    });
};

const startServerClock = () => {
    const clock = document.querySelector('[data-server-clock]');

    if (!clock) {
        return;
    }

    const serverTimestamp = Number(clock.dataset.serverTimestamp);
    const timezone = clock.dataset.serverTimezone;

    if (!Number.isFinite(serverTimestamp) || !timezone) {
        return;
    }

    const clientTimestampAtSync = Date.now();
    const formatter = new Intl.DateTimeFormat('id-ID', {
        timeZone: timezone,
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        hour12: false,
    });

    const updateClock = () => {
        const elapsed = Date.now() - clientTimestampAtSync;
        clock.textContent = formatter.format(new Date(serverTimestamp + elapsed));
    };

    updateClock();
    window.setInterval(updateClock, 1000);
};

startServerClock();

window.addEventListener('livewire:init', () => {
    Livewire.interceptRequest(({ onError, onFailure }) => {
        onError(({ response, preventDefault }) => {
            const message = toastHttpErrors[response.status]
                ?? 'Terjadi kesalahan saat memproses permintaan. Silakan coba lagi.';

            dispatchErrorToast(message);
            preventDefault();
        });

        onFailure(() => {
            dispatchErrorToast('Tidak dapat terhubung ke server. Periksa koneksi Anda lalu coba lagi.');
        });
    });

    document.addEventListener('submit', (event) => {
        if (event.target.matches('form[wire\\:submit="save"]')) {
            shouldFocusValidationError = true;
        }
    });

    Livewire.hook('commit', ({ succeed }) => {
        succeed(() => {
            if (! shouldFocusValidationError) {
                return;
            }

            shouldFocusValidationError = false;
            focusFirstValidationError();
        });
    });

    window.setInterval(() => {
        if (document.visibilityState !== 'visible') {
            return;
        }

        const path = window.location.pathname.replace(/\/+$/, '');

        if (path === '/dashboard') {
            Livewire.all().forEach((component) => {
                component.$wire.$refresh();
            });
            return;
        }

        const isOutgoingLetterDetail = /^\/outgoing-letters\/[^/]+$/.test(path);

        if (isOutgoingLetterDetail) {
            Livewire.dispatch('outgoing-letters-refresh');
        }
    }, 15000);
});
