/* Halaman profil: konfirmasi sebelum logout. */
document.addEventListener('DOMContentLoaded', function () {
    'use strict';

    const formLogout = document.getElementById('formLogout');

    if (!formLogout) {
        return;
    }

    formLogout.addEventListener('submit', function (event) {
        if (!window.confirm('Apakah Anda yakin ingin keluar dari akun ini?')) {
            event.preventDefault();
        }
    });
});
