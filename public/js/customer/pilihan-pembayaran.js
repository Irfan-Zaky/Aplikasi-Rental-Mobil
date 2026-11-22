/* Halaman pilihan pembayaran: arahkan ke halaman pembayaran sesuai metode. */
document.addEventListener('DOMContentLoaded', function () {
    'use strict';

    const formPembayaran = document.getElementById('formPembayaran');

    if (!formPembayaran) {
        return;
    }

    formPembayaran.addEventListener('submit', function (event) {
        event.preventDefault();

        const metodeTerpilih = formPembayaran.querySelector('input[name="pembayaran"]:checked');

        if (!metodeTerpilih) {
            window.alert('Silakan pilih metode pembayaran terlebih dahulu!');

            return;
        }

        const urlQris = formPembayaran.dataset.urlQris;
        const urlTunai = formPembayaran.dataset.urlTunai;

        if (metodeTerpilih.value === 'qris' && urlQris) {
            window.location.href = urlQris;
        } else if (metodeTerpilih.value === 'tunai' && urlTunai) {
            window.location.href = urlTunai;
        }
    });
});
