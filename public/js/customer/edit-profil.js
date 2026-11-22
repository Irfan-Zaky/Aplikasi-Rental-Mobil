/* Halaman edit profil: pratinjau foto, konfirmasi password, dan status loading. */
document.addEventListener('DOMContentLoaded', function () {
    'use strict';

    const inputFoto = document.getElementById('foto-profil');
    const previewFoto = document.getElementById('preview-foto-profil');
    const teksUnggah = document.getElementById('teks-unggah-foto');
    const hapusFoto = document.getElementById('hapus-foto-profil');

    if (inputFoto && previewFoto && teksUnggah) {
        const pratinjauAwal = {
            src: previewFoto.src,
            className: previewFoto.className,
            width: previewFoto.getAttribute('width'),
            height: previewFoto.getAttribute('height'),
            teks: teksUnggah.textContent.trim(),
        };
        let objectUrl = null;

        function lepasObjectUrl() {
            if (objectUrl) {
                URL.revokeObjectURL(objectUrl);
                objectUrl = null;
            }
        }

        function kembalikanPratinjau(teks) {
            lepasObjectUrl();
            previewFoto.src = pratinjauAwal.src;
            previewFoto.className = pratinjauAwal.className;

            if (pratinjauAwal.width) {
                previewFoto.setAttribute('width', pratinjauAwal.width);
            }

            if (pratinjauAwal.height) {
                previewFoto.setAttribute('height', pratinjauAwal.height);
            }

            teksUnggah.textContent = teks || pratinjauAwal.teks;
        }

        inputFoto.addEventListener('change', function () {
            const file = inputFoto.files[0];

            if (!file) {
                kembalikanPratinjau();

                return;
            }

            lepasObjectUrl();

            if (hapusFoto) {
                hapusFoto.checked = false;
            }

            objectUrl = URL.createObjectURL(file);
            previewFoto.src = objectUrl;
            previewFoto.className = 'rounded-circle object-fit-cover mb-2';
            previewFoto.setAttribute('width', 80);
            previewFoto.setAttribute('height', 80);
            teksUnggah.textContent = 'Foto baru siap disimpan';
        });

        if (hapusFoto) {
            hapusFoto.addEventListener('change', function () {
                if (hapusFoto.checked) {
                    inputFoto.value = '';
                    kembalikanPratinjau('Foto akan dihapus saat disimpan');
                } else {
                    kembalikanPratinjau();
                }
            });
        }
    }

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
