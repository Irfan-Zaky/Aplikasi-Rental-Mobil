/* Halaman pilih tanggal: sinkronkan batas tanggal mulai dan selesai. */
document.addEventListener('DOMContentLoaded', function () {
    'use strict';

    const inputMulai = document.getElementById('tanggal-mulai');
    const inputSelesai = document.getElementById('tanggal-selesai');

    if (!inputMulai || !inputSelesai) {
        return;
    }

    function formatTanggal(date) {
        const tahun = date.getFullYear();
        const bulan = String(date.getMonth() + 1).padStart(2, '0');
        const hari = String(date.getDate()).padStart(2, '0');

        return tahun + '-' + bulan + '-' + hari;
    }

    inputMulai.min = formatTanggal(new Date());

    function perbaruiBatasTanggal() {
        if (!inputMulai.value) {
            return;
        }

        inputSelesai.min = inputMulai.value;

        if (inputSelesai.value && inputSelesai.value < inputMulai.value) {
            inputSelesai.value = inputMulai.value;
        }
    }

    inputMulai.addEventListener('change', perbaruiBatasTanggal);
    perbaruiBatasTanggal();
});
