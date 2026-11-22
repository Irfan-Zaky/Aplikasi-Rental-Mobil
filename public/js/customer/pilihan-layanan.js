/* Halaman pilihan layanan: arahkan ke detail layanan sesuai pilihan. */
document.addEventListener('DOMContentLoaded', function () {
    'use strict';

    const formLayanan = document.getElementById('formLayanan');

    if (!formLayanan) {
        return;
    }

    formLayanan.addEventListener('submit', function (event) {
        event.preventDefault();

        const pilihanAktif = formLayanan.querySelector('input[name="layanan"]:checked');

        if (!pilihanAktif) {
            return;
        }

        const urlSopir = formLayanan.dataset.urlSopir;
        const urlLepasKunci = formLayanan.dataset.urlLepasKunci;

        if (pilihanAktif.value === 'dengan-sopir' && urlSopir) {
            window.location.href = urlSopir;
        } else if (pilihanAktif.value === 'lepas-kunci' && urlLepasKunci) {
            window.location.href = urlLepasKunci;
        }
    });
});
