/* Halaman syarat & ketentuan: aktifkan tombol setelah checkbox disetujui. */
document.addEventListener('DOMContentLoaded', function () {
    'use strict';

    const checkboxSyarat = document.getElementById('setuju-syarat');
    const tombolSubmit = document.getElementById('btn-submit');

    if (!checkboxSyarat || !tombolSubmit) {
        return;
    }

    function perbaruiTombol() {
        tombolSubmit.disabled = !checkboxSyarat.checked;
    }

    checkboxSyarat.addEventListener('change', perbaruiTombol);
    perbaruiTombol();
});
