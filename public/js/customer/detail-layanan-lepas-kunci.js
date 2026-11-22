/* Halaman detail layanan lepas kunci: tampilkan alamat hanya saat mobil diantar. */
document.addEventListener('DOMContentLoaded', function () {
    'use strict';

    const radioPenerimaan = document.querySelectorAll('input[name="metode-penerimaan"]');
    const wadahLokasi = document.getElementById('wadah-lokasi-pengantaran');
    const inputLokasi = document.getElementById('lokasi-pengantaran');

    if (radioPenerimaan.length === 0 || !wadahLokasi || !inputLokasi) {
        return;
    }

    function perbaruiLokasi() {
        const terpilih = document.querySelector('input[name="metode-penerimaan"]:checked');
        const diantar = terpilih && terpilih.value === 'diantar';

        wadahLokasi.classList.toggle('d-none', !diantar);
        inputLokasi.required = diantar;

        if (!diantar) {
            inputLokasi.value = '';
        }
    }

    radioPenerimaan.forEach(function (radio) {
        radio.addEventListener('change', perbaruiLokasi);
    });

    perbaruiLokasi();
});
