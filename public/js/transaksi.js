/* Halaman transaksi: membuka/menutup pilihan metode konfirmasi (interaksi UI saja). */
document.addEventListener('DOMContentLoaded', function () {
    'use strict';

    const tombolKonfirmasi = document.querySelector('.button-konfirmasi');
    const tombolQris = document.querySelector('.button-qris');
    const tombolTunai = document.querySelector('.button-tunai');
    const wadahTombol = document.querySelector('.button-container');

    if (!tombolKonfirmasi || !tombolQris || !tombolTunai || !wadahTombol) {
        return;
    }

    tombolKonfirmasi.addEventListener('click', function () {
        wadahTombol.classList.toggle('active');
        tombolKonfirmasi.classList.toggle('active');
        tombolQris.classList.toggle('active');
        tombolTunai.classList.toggle('active');
    });
});
