/*
 * ============================================================
 *  LAPISAN DATA MOBIL (penyimpanan sementara di browser)
 * ============================================================
 * Semua halaman admin memakai MobilStore supaya alur tambah, detail,
 * edit, dan hapus memakai satu sumber data yang sama.
 *
 * Saat backend + database siap, cukup ganti isi fungsi di dalam
 * MobilStore dengan pemanggilan fetch(), misalnya:
 *   list()        -> GET    /api/mobil
 *   get(id)       -> GET    /api/mobil/:id
 *   create(data)  -> POST   /api/mobil
 *   update(id, d) -> PUT    /api/mobil/:id
 *   remove(id)    -> DELETE /api/mobil/:id
 * Semua fungsi sudah mengembalikan Promise, jadi halaman tidak perlu
 * diubah lagi ketika backend dipasang.
 *
 * Foto saat ini disimpan sebagai data URL (diperkecil lebih dulu) agar
 * bisa dibaca halaman lain. Di backend nanti cukup unggah file asli
 * lalu simpan URL hasil unggahan pada properti `fotos[].src`.
 */
(function (global) {
    'use strict';

    var KUNCI_PENYIMPANAN = 'dbMobil';
    var FOTO_DEFAULT = '../img_admin/data-mobil/source/1.jpg';

    var BATAS_UKURAN_FOTO = 8 * 1024 * 1024; // 8 MB per file, sama dengan teks di modal
    var SISI_MAKS_SIMPAN = 1000;             // foto diperkecil agar localStorage tidak penuh
    var MUTU_SIMPAN = 0.75;

    // Satu sumber status: dipakai badge di tabel, filter, dan dropdown form.
    var KELAS_STATUS = {
        Tersedia: 'success',
        Disewa: 'secondary',
        Perbaikan: 'warning',
        Nonaktif: 'danger'
    };

    // Data contoh saat localStorage masih kosong (id tetap supaya link ?id= stabil).
    var DATA_AWAL = [
        {
            id: 'mbl-001',
            merk: 'Toyota',
            nama: 'Avanza 1.5 G',
            jenis: 'MPV',
            status: 'Tersedia',
            harga: 'Rp 525.000',
            plat: 'B 1842 KXZ',
            spesifikasi: 'Otomatis, 7 penumpang, bensin, 2023, AC double blower, bagasi luas',
            galeri: ['1', '2', '3']
        },
        {
            id: 'mbl-002',
            merk: 'Honda',
            nama: 'BR-V Prestige',
            jenis: 'SUV',
            status: 'Disewa',
            harga: 'Rp 675.000',
            plat: 'B 1908 PQR',
            spesifikasi: 'Otomatis, 7 penumpang, bensin, 2024, AC double blower, kamera mundur',
            galeri: ['3', '4', '5']
        },
        {
            id: 'mbl-003',
            merk: 'Suzuki',
            nama: 'Jimmy AT',
            jenis: 'SUV',
            status: 'Perbaikan',
            harga: 'Rp 950.000',
            plat: 'B 1122 SLM',
            spesifikasi: 'Otomatis, 4 penumpang, bensin, 2023, AC, 4WD',
            galeri: ['2', '3', '4']
        },
        {
            id: 'mbl-004',
            merk: 'Honda',
            nama: 'Brio Satya',
            jenis: 'Hatchback',
            status: 'Nonaktif',
            harga: 'Rp 425.000',
            plat: 'B 1500 TRX',
            spesifikasi: 'Manual, 5 penumpang, bensin, 2022, AC, audio',
            galeri: ['4', '5', '1']
        }
    ];

    function alamatFoto(nomor) {
        return '../img_admin/data-mobil/source/' + nomor + '.jpg';
    }

    function fotoDariGaleri(galeri) {
        return (galeri || []).map(function (nomor, index) {
            return {
                id: 'foto-awal-' + nomor,
                nama: 'foto-' + nomor + '.jpg',
                src: alamatFoto(nomor),
                utama: index === 0
            };
        });
    }

    function buatId() {
        return 'mbl-' + Date.now().toString(36) + Math.random().toString(36).slice(2, 7);
    }

    // Menyeragamkan bentuk data, termasuk data lama yang belum punya `fotos`.
    function normalisasi(mobil) {
        var fotoMentah = mobil.fotos;

        if (!fotoMentah && mobil.galeri) {
            fotoMentah = fotoDariGaleri(mobil.galeri);
        }

        var fotos = (fotoMentah || [])
            .filter(function (foto) { return foto && foto.src; })
            .map(function (foto, index) {
                return {
                    id: foto.id || 'foto-' + index,
                    nama: foto.nama || 'Foto mobil ' + (index + 1),
                    src: foto.src,
                    utama: !!foto.utama
                };
            });

        if (fotos.length === 0 && mobil.foto) {
            fotos = [{ id: 'foto-utama', nama: 'Foto utama', src: mobil.foto, utama: true }];
        }

        if (fotos.length > 0 && !fotos.some(function (foto) { return foto.utama; })) {
            fotos[0].utama = true;
        }

        var fotoUtama = fotos.filter(function (foto) { return foto.utama; })[0];

        return {
            id: mobil.id || buatId(),
            merk: mobil.merk || '',
            nama: mobil.nama || '',
            jenis: mobil.jenis || '',
            status: KELAS_STATUS[mobil.status] ? mobil.status : 'Tersedia',
            harga: mobil.harga || '',
            plat: mobil.plat || '',
            spesifikasi: mobil.spesifikasi || '',
            foto: fotoUtama ? fotoUtama.src : FOTO_DEFAULT,
            fotos: fotos
        };
    }

    function bacaMentah() {
        try {
            var isi = localStorage.getItem(KUNCI_PENYIMPANAN);
            if (!isi) return null;
            var data = JSON.parse(isi);
            return Array.isArray(data) ? data : null;
        } catch (error) {
            return null;
        }
    }

    function simpanSemua(daftar) {
        try {
            localStorage.setItem(KUNCI_PENYIMPANAN, JSON.stringify(daftar));
        } catch (error) {
            throw new Error('Penyimpanan browser penuh. Hapus beberapa foto lalu coba lagi.');
        }
    }

    function bacaSemua() {
        var daftar = bacaMentah();

        if (!daftar) {
            daftar = DATA_AWAL.map(normalisasi);
            simpanSemua(daftar);
            return daftar;
        }

        return daftar.map(normalisasi);
    }

    function escapeHtml(teks) {
        return String(teks === undefined || teks === null ? '' : teks).replace(/[&<>"']/g, function (karakter) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[karakter];
        });
    }

    function kelasStatus(status) {
        return KELAS_STATUS[status] || 'secondary';
    }

    // File foto diperkecil dan diubah ke data URL agar bisa disimpan & dibaca halaman lain.
    function keDataUrl(file) {
        return new Promise(function (resolve, reject) {
            var pembaca = new FileReader();

            pembaca.onerror = function () { reject(new Error('Foto "' + file.name + '" tidak bisa dibaca.')); };

            pembaca.onload = function () {
                var gambar = new Image();

                gambar.onerror = function () { reject(new Error('Foto "' + file.name + '" tidak bisa dibuka.')); };

                gambar.onload = function () {
                    var skala = Math.min(1, SISI_MAKS_SIMPAN / Math.max(gambar.width, gambar.height));
                    var kanvas = document.createElement('canvas');
                    kanvas.width = Math.round(gambar.width * skala);
                    kanvas.height = Math.round(gambar.height * skala);

                    var konteks = kanvas.getContext('2d');
                    konteks.fillStyle = '#ffffff';
                    konteks.fillRect(0, 0, kanvas.width, kanvas.height);
                    konteks.drawImage(gambar, 0, 0, kanvas.width, kanvas.height);

                    resolve(kanvas.toDataURL('image/jpeg', MUTU_SIMPAN));
                };

                gambar.src = pembaca.result;
            };

            pembaca.readAsDataURL(file);
        });
    }

    var MobilFoto = {
        FOTO_DEFAULT: FOTO_DEFAULT,

        valid: function (file) {
            var tipeValid = file.type === 'image/jpeg' || file.type === 'image/png';
            return tipeValid && file.size <= BATAS_UKURAN_FOTO;
        },

        keDataUrl: keDataUrl,

        // Mengubah foto yang masih berupa file menjadi data URL siap simpan.
        siapkan: function (daftarFoto) {
            return Promise.all((daftarFoto || []).map(function (foto) {
                if (!foto.file) {
                    return Promise.resolve({ id: foto.id, nama: foto.nama, src: foto.src, utama: !!foto.utama });
                }

                return keDataUrl(foto.file).then(function (src) {
                    return { id: foto.id, nama: foto.nama, src: src, utama: !!foto.utama };
                });
            }));
        }
    };

    var MobilStore = {
        list: function () {
            return Promise.resolve(bacaSemua());
        },

        get: function (id) {
            return Promise.resolve(bacaSemua().filter(function (mobil) { return mobil.id === id; })[0] || null);
        },

        create: function (data) {
            try {
                var daftar = bacaSemua();
                var mobilBaru = normalisasi(Object.assign({}, data, { id: data && data.id ? data.id : buatId() }));

                daftar.push(mobilBaru);
                simpanSemua(daftar);

                return Promise.resolve(mobilBaru);
            } catch (error) {
                return Promise.reject(error);
            }
        },

        update: function (id, data) {
            try {
                var daftar = bacaSemua();
                var index = daftar.findIndex(function (mobil) { return mobil.id === id; });

                if (index === -1) {
                    return Promise.reject(new Error('Data mobil tidak ditemukan.'));
                }

                daftar[index] = normalisasi(Object.assign({}, daftar[index], data, { id: id }));
                simpanSemua(daftar);

                return Promise.resolve(daftar[index]);
            } catch (error) {
                return Promise.reject(error);
            }
        },

        remove: function (id) {
            try {
                var daftar = bacaSemua().filter(function (mobil) { return mobil.id !== id; });
                simpanSemua(daftar);
                return Promise.resolve(true);
            } catch (error) {
                return Promise.reject(error);
            }
        }
    };

    global.MobilStore = MobilStore;
    global.MobilFoto = MobilFoto;
    global.MobilUtil = {
        escapeHtml: escapeHtml,
        kelasStatus: kelasStatus,
        FOTO_DEFAULT: FOTO_DEFAULT,
        daftarStatus: Object.keys(KELAS_STATUS)
    };
})(window);
