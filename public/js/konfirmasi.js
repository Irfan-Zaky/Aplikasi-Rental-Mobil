/* Konfirmasi browser sebelum form aksi dikirim ke backend PHP. */
(function () {
    'use strict';

    if (window.konfirmasiFormListenerLoaded === true) {
        return;
    }

    window.konfirmasiFormListenerLoaded = true;

    document.addEventListener('submit', function (event) {
        const form = event.target;

        if (!(form instanceof HTMLFormElement) || !form.classList.contains('form-konfirmasi')) {
            return;
        }

        const pesan = form.dataset.pesan || 'Yakin ingin melanjutkan aksi ini?';

        if (!window.confirm(pesan)) {
            event.preventDefault();
        }
    });
})();
