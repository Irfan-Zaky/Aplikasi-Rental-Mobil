/* Halaman edit mobil: membuka modal foto dan memperbarui pratinjau foto utama. */
document.addEventListener('DOMContentLoaded', function () {
    'use strict';

    const tombolGantiFoto = document.getElementById('btn-ganti-foto');
    const gambarMobil = document.getElementById('foto-mobil');

    if (!tombolGantiFoto || !window.ModalFotoMobil) {
        return;
    }

    const modalFoto = window.ModalFotoMobil.buat({
        onSimpan: function (fotoUtama) {
            if (fotoUtama && gambarMobil) {
                gambarMobil.src = fotoUtama.src;
            }
        }
    });

    tombolGantiFoto.addEventListener('click', function () {
        if (modalFoto) {
            modalFoto.buka();
        }
    });
});
