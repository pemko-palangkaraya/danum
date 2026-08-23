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
});
