/*
 * Modal "Unggah foto mobil" - hanya mengelola interaksi antarmuka:
 * pilih, hapus, dan tentukan foto utama. Seluruh penyimpanan dan
 * pemrosesan file ditangani oleh backend PHP melalui form. Input tersembunyi
 * "foto_utama" berisi indeks foto terpilih di antara file yang diunggah.
 */
(function (global) {
    'use strict';

    var BATAS_UKURAN_FOTO = 8 * 1024 * 1024;
    var IKON_BINTANG = '<svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87L18.18 22 12 18.56 5.82 22 7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>';
    var IKON_BINTANG_KOSONG = '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round" xmlns="http://www.w3.org/2000/svg"><path d="M12 3.5l2.7 5.47 6.04.88-4.37 4.26 1.03 6.02L12 17.29l-5.4 2.84 1.03-6.02L3.26 9.85l6.04-.88L12 3.5z"/></svg>';

    function escapeHtml(teks) {
        return String(teks === undefined || teks === null ? '' : teks).replace(/[&<>"']/g, function (karakter) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[karakter];
        });
    }

    function buat(opsi) {
        opsi = opsi || {};

        var elemenModal = document.getElementById('modalFotoMobil');

        if (!elemenModal || !global.bootstrap) {
            return null;
        }

        var inputFoto = document.getElementById('input-foto-modal');
        var tombolTambahFoto = document.getElementById('btn-tambah-foto');
        var dropzone = document.getElementById('dropzone-foto');
        var daftarFotoEl = document.getElementById('daftar-foto');
        var jumlahFotoEl = document.getElementById('jumlah-foto');
        var statusFotoEl = document.getElementById('status-foto');
        var tombolSimpan = document.getElementById('btn-simpan-foto');
        var inputFotoUtama = document.getElementById('foto-utama');
        var wadahFotoLama = document.getElementById('foto-lama-container');

        var modal = new global.bootstrap.Modal(elemenModal);
        var daftarFoto = [];

        function fotoUtama() {
            return daftarFoto.filter(function (foto) { return foto.utama; })[0] || daftarFoto[0] || null;
        }

        function sinkronkanForm() {
            var utama = fotoUtama();

            if (inputFotoUtama) {
                var fotoDiunggah = daftarFoto.filter(function (foto) { return foto.file; });
                var indeksUtama = utama ? fotoDiunggah.indexOf(utama) : -1;

                inputFotoUtama.value = indeksUtama >= 0 ? String(indeksUtama) : '0';
            }

            if (global.DataTransfer && inputFoto) {
                var pemindah = new DataTransfer();

                daftarFoto.forEach(function (foto) {
                    if (foto.file) {
                        pemindah.items.add(foto.file);
                    }
                });

                inputFoto.files = pemindah.files;
            }

            if (wadahFotoLama) {
                wadahFotoLama.innerHTML = daftarFoto.filter(function (foto) { return !foto.file; }).map(function (foto) {
                    return '<input type="hidden" name="foto_lama[]" value="' + escapeHtml(foto.id) + '">';
                }).join('');
            }
        }

        function render() {
            daftarFotoEl.innerHTML = '';
            jumlahFotoEl.textContent = daftarFoto.length + ' foto';
            statusFotoEl.textContent = daftarFoto.length === 0
                ? 'Belum ada foto dipilih'
                : daftarFoto.length + ' foto siap disimpan';

            daftarFoto.forEach(function (foto) {
                var kolom = document.createElement('div');
                kolom.className = 'col-12 col-sm-6 col-md-4';
                kolom.innerHTML = '' +
                    '<div class="card h-100 rounded-4 overflow-hidden" style="border: ' + (foto.utama ? '2px solid #ff7a3d' : '1px solid #e2e8f0') + '; box-shadow: ' + (foto.utama ? '0 0 0 4px rgba(255, 122, 61, 0.10)' : 'none') + ';">' +
                        '<div class="position-relative">' +
                            '<img src="' + escapeHtml(foto.src) + '" alt="' + escapeHtml(foto.nama) + '" class="w-100 d-block object-fit-cover" style="height: 150px;" />' +
                            '<button type="button" class="btn btn-hapus-foto position-absolute d-flex align-items-center justify-content-center p-0" aria-label="Hapus foto" style="top: 8px; right: 8px; width: 26px; height: 26px; border-radius: 50%; background-color: #fff; border: 1px solid #e2e8f0; color: #0b1d33;">' +
                                '<svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M6 6l12 12M18 6L6 18"/></svg>' +
                            '</button>' +
                            (foto.utama ? '<span class="badge position-absolute d-inline-flex align-items-center gap-1 rounded-2 fw-semibold" style="left: 8px; bottom: 8px; background-color: #ff7a3d; color: #fff; font-size: 11px; padding: 7px 10px;">' + IKON_BINTANG + ' Foto utama</span>' : '') +
                        '</div>' +
                        '<div class="p-2">' +
                            '<p class="fw-semibold mb-1 text-truncate" title="' + escapeHtml(foto.nama) + '" style="color: #0b1d33; font-size: 12.5px;">' + escapeHtml(foto.nama) + '</p>' +
                            '<button type="button" class="btn btn-jadikan-utama p-0 border-0 bg-transparent d-flex align-items-center gap-1" style="color: ' + (foto.utama ? '#ff7a3d' : '#64748b') + '; font-size: 11.5px;">' +
                                (foto.utama ? IKON_BINTANG : IKON_BINTANG_KOSONG) +
                                '<span>' + (foto.utama ? 'Dipilih sebagai foto utama' : 'Jadikan foto utama') + '</span>' +
                            '</button>' +
                        '</div>' +
                    '</div>';

                kolom.querySelector('.btn-hapus-foto').addEventListener('click', function () {
                    hapusFoto(foto.id);
                });

                if (!foto.utama) {
                    kolom.querySelector('.btn-jadikan-utama').addEventListener('click', function () {
                        daftarFoto.forEach(function (item) { item.utama = item.id === foto.id; });
                        render();
                    });
                }

                daftarFotoEl.appendChild(kolom);
            });

            sinkronkanForm();
        }

        function hapusFoto(id) {
            var index = daftarFoto.findIndex(function (foto) { return foto.id === id; });

            if (index === -1) {
                return;
            }

            if (daftarFoto[index].src.indexOf('blob:') === 0) {
                global.URL.revokeObjectURL(daftarFoto[index].src);
            }

            var utamaDihapus = daftarFoto[index].utama;

            daftarFoto.splice(index, 1);

            if (utamaDihapus && daftarFoto.length > 0) {
                daftarFoto[0].utama = true;
            }

            render();
        }

        function tambahFoto(fileList) {
            var ditolak = [];

            Array.prototype.forEach.call(fileList, function (file) {
                var tipeValid = file.type === 'image/jpeg' || file.type === 'image/png';

                if (!tipeValid || file.size > BATAS_UKURAN_FOTO) {
                    ditolak.push(file.name);
                    return;
                }

                daftarFoto.push({
                    id: 'foto-' + Date.now() + '-' + Math.random().toString(16).slice(2),
                    nama: file.name,
                    src: global.URL.createObjectURL(file),
                    file: file,
                    utama: daftarFoto.length === 0
                });
            });

            if (ditolak.length > 0) {
                global.alert('Foto berikut dilewati karena harus JPG/PNG dan maksimal 8 MB:\n- ' + ditolak.join('\n- '));
            }

            render();
        }

        function muatFotoAwal() {
            var mentah = daftarFotoEl.getAttribute('data-foto-awal');

            if (!mentah) {
                return;
            }

            try {
                var data = JSON.parse(mentah);

                daftarFoto = Array.isArray(data) ? data.filter(function (foto) { return foto && foto.src; }).map(function (foto, index) {
                    return {
                        id: foto.id || 'foto-lama-' + index,
                        nama: foto.nama || 'Foto mobil ' + (index + 1),
                        src: foto.src,
                        file: null,
                        utama: !!foto.utama
                    };
                }) : [];
            } catch (error) {
                daftarFoto = [];
            }

            if (daftarFoto.length > 0 && !fotoUtama()) {
                daftarFoto[0].utama = true;
            }

            render();
        }

        if (inputFoto) {
            inputFoto.addEventListener('change', function () {
                tambahFoto(this.files);
                this.value = '';
            });
        }

        [dropzone, tombolTambahFoto].forEach(function (elemen) {
            if (!elemen) {
                return;
            }

            elemen.addEventListener('click', function (event) {
                event.preventDefault();

                if (inputFoto) {
                    inputFoto.click();
                }
            });
        });

        if (dropzone) {
            dropzone.addEventListener('dragover', function (event) {
                event.preventDefault();
                dropzone.style.borderColor = '#ff7a3d';
                dropzone.style.backgroundColor = '#fff4ee';
            });

            dropzone.addEventListener('dragleave', function () {
                dropzone.style.borderColor = '#cbd5e1';
                dropzone.style.backgroundColor = '#f8fafc';
            });

            dropzone.addEventListener('drop', function (event) {
                event.preventDefault();
                dropzone.style.borderColor = '#cbd5e1';
                dropzone.style.backgroundColor = '#f8fafc';
                tambahFoto(event.dataTransfer.files);
            });
        }

        if (tombolSimpan) {
            tombolSimpan.addEventListener('click', function () {
                var utama = fotoUtama();

                sinkronkanForm();

                if (typeof opsi.onSimpan === 'function') {
                    opsi.onSimpan(utama, daftarFoto.slice());
                }

                modal.hide();
            });
        }

        muatFotoAwal();

        return {
            buka: function () {
                render();
                modal.show();
            },
            tutup: function () {
                modal.hide();
            },
            ambil: function () {
                return daftarFoto.slice();
            }
        };
    }

    global.ModalFotoMobil = { buat: buat };
})(window);
