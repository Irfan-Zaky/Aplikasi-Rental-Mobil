/* Halaman tambah mobil: menyimpan data kendaraan beserta foto yang dipilih di modal. */
document.addEventListener('DOMContentLoaded', function () {
    'use strict';

    const kotakUpload = document.getElementById('kotak-upload-foto');
    const formTambah = document.getElementById('form-tambah');
    const kotakPreview = document.getElementById('preview-foto-profil');
    const gambarPreview = document.getElementById('preview-foto-profil-img');

    const kolom = {
        merk: document.getElementById('merk'),
        nama: document.getElementById('nama-mobil'),
        jenis: document.getElementById('jenis'),
        status: document.getElementById('status'),
        harga: document.getElementById('harga-sewa'),
        plat: document.getElementById('plat-nomor'),
        spesifikasi: document.getElementById('spesifikasi')
    };

    const modalFoto = ModalFotoMobil.buat({
        onSimpan: function (fotoUtama) {
            if (fotoUtama) {
                gambarPreview.src = fotoUtama.src;
                kotakPreview.classList.remove('d-none');
            } else {
                gambarPreview.removeAttribute('src');
                kotakPreview.classList.add('d-none');
            }
        }
    });

    function bukaModal() {
        modalFoto.buka();
    }

    function bacaForm() {
        return {
            merk: kolom.merk.value.trim(),
            nama: kolom.nama.value.trim(),
            jenis: kolom.jenis.value,
            status: kolom.status.value,
            harga: kolom.harga.value.trim(),
            plat: kolom.plat.value.trim(),
            spesifikasi: kolom.spesifikasi.value.trim()
        };
    }

    kotakUpload.addEventListener('click', bukaModal);

    kotakUpload.addEventListener('keydown', function (event) {
        if (event.key === 'Enter' || event.key === ' ') {
            event.preventDefault();
            bukaModal();
        }
    });

    formTambah.addEventListener('submit', function (event) {
        event.preventDefault();

        modalFoto.siapkan().then(function (fotos) {
            const fotoUtama = fotos.filter(function (foto) { return foto.utama; })[0] || fotos[0] || null;

            return MobilStore.create(Object.assign(bacaForm(), {
                fotos: fotos,
                foto: fotoUtama ? fotoUtama.src : MobilFoto.FOTO_DEFAULT
            }));
        }).then(function () {
            alert('Mobil berhasil ditambahkan!');
            window.location.href = 'data-mobil.html';
        }).catch(function (error) {
            alert(error.message || 'Data mobil gagal disimpan.');
        });
    });
});
