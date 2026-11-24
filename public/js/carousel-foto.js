/* Sembunyikan kontrol carousel bila foto yang ditampilkan hanya satu. */
document.addEventListener('DOMContentLoaded', function () {
    'use strict';

    const carousel = document.getElementById('carouselCarPhoto');

    if (!carousel) {
        return;
    }

    const jumlahFoto = carousel.querySelectorAll('.carousel-item').length;
    const indikator = carousel.querySelector('.carousel-indicators');
    const tombolPrev = carousel.querySelector('.carousel-control-prev');
    const tombolNext = carousel.querySelector('.carousel-control-next');

    if (jumlahFoto > 1) {
        return;
    }

    [indikator, tombolPrev, tombolNext].forEach(function (elemen) {
        if (elemen) {
            elemen.classList.add('d-none');
        }
    });
});
