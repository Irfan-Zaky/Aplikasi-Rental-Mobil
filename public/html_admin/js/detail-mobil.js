/* Halaman detail mobil: menampilkan data mobil sesuai ?id= beserta carousel fotonya. */
document.addEventListener('DOMContentLoaded', function () {
    'use strict';

    const idMobil = new URLSearchParams(window.location.search).get('id');

    const carousel = document.getElementById('carouselFotoMobil');
    const carouselInner = document.getElementById('carousel-foto-inner');
    const carouselIndikator = document.getElementById('carousel-foto-indikator');
    const tombolPrev = carousel.querySelector('.carousel-control-prev');
    const tombolNext = carousel.querySelector('.carousel-control-next');

    const kolom = {
        merk: document.getElementById('merk'),
        nama: document.getElementById('nama-mobil'),
        jenis: document.getElementById('jenis'),
        status: document.getElementById('status'),
        harga: document.getElementById('harga-sewa'),
        plat: document.getElementById('plat-nomor'),
        spesifikasi: document.getElementById('spesifikasi')
    };

    function renderCarousel(fotos) {
        const esc = MobilUtil.escapeHtml;

        // Foto utama tampil lebih dulu.
        const daftar = (fotos && fotos.length ? fotos.slice() : [{ nama: 'Foto mobil', src: MobilFoto.FOTO_DEFAULT, utama: true }])
            .sort(function (a, b) { return (b.utama ? 1 : 0) - (a.utama ? 1 : 0); });

        carouselInner.innerHTML = daftar.map(function (foto, index) {
            return `<div class="carousel-item ${index === 0 ? 'active' : ''}">
                        <img src="${esc(foto.src)}" class="d-block w-100 object-fit-cover" style="height: 220px;" alt="${esc(foto.nama || 'Foto mobil ' + (index + 1))}" />
                    </div>`;
        }).join('');

        carouselIndikator.innerHTML = daftar.map(function (foto, index) {
            return `<button type="button" data-bs-target="#carouselFotoMobil" data-bs-slide-to="${index}" class="${index === 0 ? 'active' : ''}" ${index === 0 ? 'aria-current="true"' : ''} aria-label="Foto ${index + 1}"></button>`;
        }).join('');

        // Kontrol disembunyikan kalau foto hanya satu.
        const satuFoto = daftar.length < 2;
        carouselIndikator.classList.toggle('d-none', satuFoto);
        tombolPrev.classList.toggle('d-none', satuFoto);
        tombolNext.classList.toggle('d-none', satuFoto);
    }

    function tampilkanMobil(mobil) {
        kolom.merk.value = mobil.merk;
        kolom.nama.value = mobil.nama;
        kolom.jenis.value = mobil.jenis;
        kolom.status.value = mobil.status;
        kolom.harga.value = mobil.harga;
        kolom.plat.value = mobil.plat;
        kolom.spesifikasi.value = mobil.spesifikasi;

        document.getElementById('kode-mobil').textContent = 'Kode_Mobil.' + mobil.id;
        document.getElementById('btn-edit-mobil').href = 'edit-mobil.html?id=' + encodeURIComponent(mobil.id);

        renderCarousel(mobil.fotos);
    }

    if (!idMobil) {
        window.location.replace('data-mobil.html');
        return;
    }

    MobilStore.get(idMobil).then(function (mobil) {
        if (!mobil) {
            window.location.replace('data-mobil.html');
            return;
        }

        tampilkanMobil(mobil);
    }).catch(function () {
        window.location.replace('data-mobil.html');
    });
});
