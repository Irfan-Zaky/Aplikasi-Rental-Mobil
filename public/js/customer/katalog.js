/* Halaman katalog: kalender dengan Enter, pencarian, dan filter via modal. */
document.addEventListener('DOMContentLoaded', function () {
    'use strict';

    // 1. Kalender rentang tanggal sewa. Enter dipakai untuk menerapkan pilihan.
    const teksMulai = document.getElementById('teks-tgl-mulai');
    const teksSelesai = document.getElementById('teks-tgl-selesai');
    const jangkarKalender = document.getElementById('jangkar-kalender');
    const tombolUbahTanggal = document.getElementById('btn-ubah-tanggal');
    let kalenderSewa = null;
    let kalenderSedangDibuka = false;

    function tampilkanTanggal(instance, tanggalTerpilih) {
        if (tanggalTerpilih.length !== 2 || !teksMulai || !teksSelesai) {
            return;
        }

        teksMulai.textContent = instance.formatDate(tanggalTerpilih[0], 'd M Y');
        teksSelesai.textContent = instance.formatDate(tanggalTerpilih[1], 'd M Y');
    }

    if (jangkarKalender && typeof flatpickr === 'function') {
        kalenderSewa = flatpickr(jangkarKalender, {
            mode: 'range',
            minDate: 'today',
            dateFormat: 'd M Y',
            showMonths: 1,
            closeOnSelect: false,
            onOpen: function () {
                kalenderSedangDibuka = true;
            },
            onClose: function (selectedDates, dateStr, instance) {
                kalenderSedangDibuka = false;
                tampilkanTanggal(instance, selectedDates);
            }
        });

        if (tombolUbahTanggal) {
            tombolUbahTanggal.addEventListener('click', function () {
                kalenderSewa.open();
            });
        }
    }

    document.addEventListener('keydown', function (event) {
        if (event.key !== 'Enter' || !kalenderSedangDibuka || !kalenderSewa) {
            return;
        }

        event.preventDefault();

        if (kalenderSewa.selectedDates.length === 2) {
            tampilkanTanggal(kalenderSewa, kalenderSewa.selectedDates);
            kalenderSewa.close();
        } else {
            window.alert('Pilih tanggal selesai juga ya!');
        }
    });

    // 2. Enter pada input filter di modal mengirim form pencarian yang sama.
    const formFilter = document.getElementById('form-filter-katalog');

    function kirimFormFilter() {
        if (!formFilter) {
            return;
        }

        if (typeof formFilter.requestSubmit === 'function') {
            formFilter.requestSubmit();
        } else {
            formFilter.submit();
        }
    }

    document.querySelectorAll('#modal-filter-merk input, #modal-filter-jenis input, #modal-filter-harga input')
        .forEach(function (input) {
            input.addEventListener('keydown', function (event) {
                if (event.key !== 'Enter') {
                    return;
                }

                event.preventDefault();

                const hargaMin = document.getElementById('input-harga-min');
                const hargaMax = document.getElementById('input-harga-max');

                if (hargaMin && hargaMax && hargaMin.value !== '' && hargaMax.value !== '') {
                    if (Number(hargaMin.value) > Number(hargaMax.value)) {
                        const sementara = hargaMin.value;
                        hargaMin.value = hargaMax.value;
                        hargaMax.value = sementara;
                    }
                }

                kirimFormFilter();
            });
        });

    if (formFilter) {
        formFilter.addEventListener('submit', function () {
            const hargaMin = document.getElementById('input-harga-min');
            const hargaMax = document.getElementById('input-harga-max');

            if (hargaMin && hargaMax && hargaMin.value !== '' && hargaMax.value !== '') {
                if (Number(hargaMin.value) > Number(hargaMax.value)) {
                    const sementara = hargaMin.value;
                    hargaMin.value = hargaMax.value;
                    hargaMax.value = sementara;
                }
            }
        });
    }
});
