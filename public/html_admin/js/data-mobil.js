/* Halaman daftar mobil: render tabel, pencarian, filter, dan hapus data. */
document.addEventListener('DOMContentLoaded', function () {
    'use strict';

    const tbody = document.getElementById('body-tabel-mobil');
    const formFilter = document.getElementById('form-filter');
    const inputCari = formFilter.querySelector('input[name="keyword"]');
    const pilihMerk = document.getElementById('merk');
    const pilihJenis = document.getElementById('jenis');
    const pilihStatus = document.getElementById('status');
    const infoJumlah = document.getElementById('jumlah-armada');

    let semuaMobil = [];

    function teksNormal(teks) {
        return String(teks || '').toLowerCase().replace(/[^a-z0-9]/g, '');
    }

    function saringMobil() {
        const kata = inputCari.value.trim().toLowerCase();
        const merk = pilihMerk.value;
        const jenis = pilihJenis.value;
        const status = pilihStatus.value;

        return semuaMobil.filter(function (mobil) {
            const teksCari = (mobil.merk + ' ' + mobil.nama + ' ' + mobil.plat).toLowerCase();

            if (kata && teksCari.indexOf(kata) === -1) return false;
            if (merk && teksNormal(mobil.merk + ' ' + mobil.nama).indexOf(merk) === -1) return false;
            if (jenis && mobil.jenis !== jenis) return false;
            if (status && String(mobil.status).toLowerCase() !== status) return false;

            return true;
        });
    }

    function barisKosong() {
        const tr = document.createElement('tr');
        tr.innerHTML = '<td colspan="7" class="text-center text-muted small py-4">Tidak ada mobil yang cocok dengan pencarian.</td>';
        return tr;
    }

    function render() {
        const daftar = saringMobil();

        infoJumlah.textContent = semuaMobil.length + ' unit terdaftar \u00b7 Total Armada';
        tbody.innerHTML = '';

        if (daftar.length === 0) {
            tbody.appendChild(barisKosong());
            return;
        }

        daftar.forEach(function (mobil) {
            const esc = MobilUtil.escapeHtml;
            const kelas = MobilUtil.kelasStatus(mobil.status);
            const id = encodeURIComponent(mobil.id);

            const tr = document.createElement('tr');
            tr.className = 'border-bottom';
            tr.innerHTML = `
                <td class="ps-3"><img src="${esc(mobil.foto)}" alt="" class="rounded-3 object-fit-cover shadow-sm" width="70" height="45" /></td>
                <td class="fw-medium">${esc(mobil.merk)}</td>
                <td class="fw-bold">${esc(mobil.nama)}</td>
                <td class="text-secondary">${esc(mobil.jenis)}</td>
                <td class="fw-medium">${esc(mobil.harga)}</td>
                <td><span class="badge bg-${kelas} bg-opacity-10 text-${kelas} border border-${kelas} px-3 py-2 rounded-pill fw-medium">${esc(mobil.status)}</span></td>
                <td class="pe-3">
                    <div class="d-flex gap-1">
                        <a href="detail-mobil.html?id=${id}" class="btn btn-sm px-3 rounded-3 text-decoration-none" style="background-color: transparent; border: 1px solid #6c757d; color: BLACK;">Detail</a>
                        <a href="edit-mobil.html?id=${id}" class="btn btn-sm px-3 rounded-3 text-decoration-none" style="background-color: transparent; border: 1px solid #6c757d; color: BLACK;">Edit</a>
                        <button type="button" data-hapus="${mobil.id}" class="btn btn-sm px-3 rounded-3" style="background-color: transparent; border: 1px solid #dc3545; color: #dc3545;">Hapus</button>
                    </div>
                </td>
            `;

            tbody.appendChild(tr);
        });
    }

    formFilter.addEventListener('submit', function (event) {
        event.preventDefault();
        render();
    });

    formFilter.addEventListener('input', render);
    formFilter.addEventListener('change', render);

    // Hapus memakai event delegation supaya tetap jalan setelah tabel di-render ulang.
    tbody.addEventListener('click', function (event) {
        const tombol = event.target.closest('[data-hapus]');
        if (!tombol) return;

        if (!confirm('Yakin ingin menghapus mobil ini?')) return;

        const id = tombol.getAttribute('data-hapus');

        MobilStore.remove(id).then(function () {
            semuaMobil = semuaMobil.filter(function (mobil) { return mobil.id !== id; });
            render();
        }).catch(function (error) {
            alert(error.message || 'Mobil gagal dihapus.');
        });
    });

    MobilStore.list().then(function (daftar) {
        semuaMobil = daftar;
        render();
    }).catch(function (error) {
        alert(error.message || 'Data mobil gagal dimuat.');
    });
});
