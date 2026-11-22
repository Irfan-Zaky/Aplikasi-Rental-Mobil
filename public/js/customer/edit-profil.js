/* Halaman edit profil: validasi konfirmasi password dan status loading. */
document.addEventListener('DOMContentLoaded', function () {
    'use strict';

    const formProfil = document.getElementById('editProfilForm');
    const tombolSimpan = document.getElementById('btnSimpan');
    const password = document.getElementById('password');
    const konfirmasi = document.getElementById('konfirmasi-password');
    const messageBox = document.getElementById('messageBox');

    if (!formProfil || !tombolSimpan || !password || !konfirmasi || !messageBox) {
        return;
    }

    function tampilkanPesan(pesan, tipe) {
        messageBox.textContent = pesan;
        messageBox.className = 'text-' + tipe + ' text-center mb-2 fw-medium fs-6';
    }

    formProfil.addEventListener('submit', function (event) {
        if (password.value !== '' && password.value !== konfirmasi.value) {
            event.preventDefault();
            tampilkanPesan('Password Baru dan Konfirmasi tidak cocok!', 'danger');

            return;
        }

        messageBox.classList.add('d-none');
        tombolSimpan.disabled = true;
        tombolSimpan.textContent = 'Menyimpan...';
    });
});
