/* Halaman daftar akun: validasi konfirmasi password dan status loading. */
document.addEventListener('DOMContentLoaded', function () {
    'use strict';

    const formDaftar = document.getElementById('registerForm');
    const tombolDaftar = document.getElementById('registerBtn');
    const password = document.getElementById('password');
    const konfirmasi = document.getElementById('konfirmasi-password');
    const messageBox = document.getElementById('messageBox');

    if (!formDaftar || !tombolDaftar || !password || !konfirmasi || !messageBox) {
        return;
    }

    function tampilkanPesan(pesan, tipe) {
        messageBox.textContent = pesan;
        messageBox.className = 'text-' + tipe + ' text-center mb-2 fw-medium fs-6';
    }

    formDaftar.addEventListener('submit', function (event) {
        if (password.value !== konfirmasi.value) {
            event.preventDefault();
            tampilkanPesan('Password dan Konfirmasi tidak cocok!', 'danger');

            return;
        }

        messageBox.classList.add('d-none');
        tombolDaftar.disabled = true;
        tombolDaftar.textContent = 'Memproses...';
    });
});
