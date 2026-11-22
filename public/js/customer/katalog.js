/* Halaman katalog: kalender pop-up dan status tombol filter. */
document.addEventListener('DOMContentLoaded', function () {
    'use strict';

    const teksMulai = document.getElementById('teks-tgl-mulai');
    const teksSelesai = document.getElementById('teks-tgl-selesai');
    const jangkarKalender = document.getElementById('jangkar-kalender');
    const tombolUbahTanggal = document.getElementById('btn-ubah-tanggal');

    if (jangkarKalender && typeof flatpickr === 'function') {
        const kalenderSewa = flatpickr(jangkarKalender, {
            mode: 'range',
            minDate: 'today',
            dateFormat: 'd M Y',
            showMonths: 1,
            onClose: function (selectedDates, dateStr, instance) {
                if (selectedDates.length === 2 && teksMulai && teksSelesai) {
                    teksMulai.textContent = instance.formatDate(selectedDates[0], 'd M Y');
                    teksSelesai.textContent = instance.formatDate(selectedDates[1], 'd M Y');
                } else if (selectedDates.length === 1) {
                    window.alert('Pilih tanggal selesai juga ya!');
                }
            }
        });

        if (tombolUbahTanggal) {
            tombolUbahTanggal.addEventListener('click', function () {
                kalenderSewa.open();
            });
        }
    }

    const tombolFilter = [
        document.getElementById('btn-semua'),
        document.getElementById('btn-jenis'),
        document.getElementById('btn-merk'),
        document.getElementById('btn-harga')
    ].filter(Boolean);

    function setTombolAktif(tombolAktif) {
        tombolFilter.forEach(function (tombol) {
            tombol.classList.remove('btn-dark');
            tombol.classList.add('btn-outline-dark', 'bg-white');
        });

        tombolAktif.classList.remove('btn-outline-dark', 'bg-white');
        tombolAktif.classList.add('btn-dark');
    }

    tombolFilter.forEach(function (tombol) {
        tombol.addEventListener('click', function () {
            setTombolAktif(tombol);
        });
    });
});
