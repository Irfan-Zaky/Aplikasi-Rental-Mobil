/* Halaman tambah mobil: membuka modal foto dan menampilkan pratinjau foto utama. */
document.addEventListener('DOMContentLoaded', function () {
    'use strict';

    const kotakUpload = document.getElementById('kotak-upload-foto');
    const wadahPreview = document.getElementById('preview-foto-profil');
    const gambarPreview = document.getElementById('preview-foto-profil-img');

    if (!kotakUpload || !window.ModalFotoMobil) {
        return;
    }

    const modalFoto = window.ModalFotoMobil.buat({
        onSimpan: function (fotoUtama) {
            if (!gambarPreview || !wadahPreview) {
                return;
            }

            if (fotoUtama) {
                gambarPreview.src = fotoUtama.src;
                wadahPreview.classList.remove('d-none');
            } else {
                gambarPreview.removeAttribute('src');
                wadahPreview.classList.add('d-none');
            }
        }
    });

    function bukaModal() {
        if (modalFoto) {
            modalFoto.buka();
        }
    }

    kotakUpload.addEventListener('click', bukaModal);

    kotakUpload.addEventListener('keydown', function (event) {
        if (event.key === 'Enter' || event.key === ' ') {
            event.preventDefault();
            bukaModal();
        }
    });
});
