/* Halaman login: tampilkan status loading saat form dikirim. */
document.addEventListener('DOMContentLoaded', function () {
    'use strict';

    const formLogin = document.getElementById('loginForm');
    const tombolLogin = document.getElementById('loginBtn');

    if (!formLogin || !tombolLogin) {
        return;
    }

    formLogin.addEventListener('submit', function () {
        tombolLogin.disabled = true;
        tombolLogin.textContent = 'Memuat...';
    });
});
