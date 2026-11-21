/* Halaman edit mobil: memuat data sesuai ?id=, lalu menyimpan perubahan beserta fotonya. */
document.addEventListener('DOMContentLoaded', function () {
    'use strict';

    const idMobil = new URLSearchParams(window.location.search).get('id');

    const gambarMobil = document.getElementById('foto-mobil');
    const tombolGantiFoto = document.getElementById('btn-ganti-foto');
    const formEdit = document.getElementById('form-edit');

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
            if (fotoUtama) gambarMobil.src = fotoUtama.src;
        }
    });

    let mobilAktif = null;

    function kembaliKeDaftar() {
        alert('Data mobil tidak ditemukan. Silakan pilih mobil dari daftar.');
        window.location.replace('data-mobil.html');
    }

    function isiForm(mobil) {
        kolom.merk.value = mobil.merk;
        kolom.nama.value = mobil.nama;
        kolom.jenis.value = mobil.jenis;
        kolom.status.value = mobil.status;
        kolom.harga.value = mobil.harga;
        kolom.plat.value = mobil.plat;
        kolom.spesifikasi.value = mobil.spesifikasi;

        gambarMobil.src = mobil.foto;
        modalFoto.set(mobil.fotos);
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

    tombolGantiFoto.addEventListener('click', function () {
        modalFoto.buka();
    });

    formEdit.addEventListener('submit', function (event) {
        event.preventDefault();
        if (!mobilAktif) return;

        modalFoto.siapkan().then(function (fotos) {
            const fotoUtama = fotos.filter(function (foto) { return foto.utama; })[0] || fotos[0] || null;

            return MobilStore.update(idMobil, Object.assign(bacaForm(), {
                fotos: fotos,
                foto: fotoUtama ? fotoUtama.src : MobilFoto.FOTO_DEFAULT
            }));
        }).then(function () {
            alert('Data berhasil diubah!');
            window.location.href = 'data-mobil.html';
        }).catch(function (error) {
            alert(error.message || 'Perubahan gagal disimpan.');
        });
    });

    if (!idMobil) {
        kembaliKeDaftar();
        return;
    }

    MobilStore.get(idMobil).then(function (mobil) {
        if (!mobil) {
            kembaliKeDaftar();
            return;
        }

        mobilAktif = mobil;
        isiForm(mobil);
    }).catch(kembaliKeDaftar);
});
