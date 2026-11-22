/* Halaman pilihan layanan: pastikan salah satu layanan dipilih sebelum submit. */
document.addEventListener('DOMContentLoaded', function () {
    'use strict';

    const formLayanan = document.getElementById('formLayanan');

    if (!formLayanan) {
        return;
    }

    formLayanan.addEventListener('submit', function (event) {
        if (!formLayanan.querySelector('input[name="layanan"]:checked')) {
            event.preventDefault();
        }
    });
});
