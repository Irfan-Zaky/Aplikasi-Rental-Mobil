# Rencana Minggu 10 — GoRental

Dibuat: 24 November 2026.

File ini adalah **catatan perencanaan dan pembagian kepingan kerja**, bukan acuan perilaku. Acuan tetap:

- `docs/usecase_gorental.md` — alur, status, aturan bisnis.
- `docs/database_gorental.md` — nama tabel, kolom, tipe, enum.
- `docs/spesifikasi_laravel_gorental.md` — rancangan lama, **tidak diubah**.

Database kerja: `pbl-113-coba` (`migrate:fresh --seed --force`).

---

## 1. Status awal hasil audit

### Target inti Minggu 10

| Target | Status awal | Keterangan |
|---|---|---|
| Pengecekan ketersediaan jadwal mobil | ✅ Utuh | `PemesananController::ketersediaanCar()` + aturan overlap/status pemblokir + modal S-01, dicek ulang dalam transaksi atomik. Di dokumen ini **C-08** (C-09 = syarat, C-10 = layanan). |
| Pembuatan kode pembayaran tunai | ⚠️ Sebagian | Kode `TNI-XXXX-XXXX` dan TTL 3 jam dibuat saat customer membuka halaman tunai. Belum ada input kode di admin A-08, validasi kode tidak ditemukan/kedaluwarsa, dan transisi ke `Menunggu Verifikasi`. |
| QRIS otomatis (autogate) + notifikasi | ❌ Belum | Baru payload QR simulasi. Tidak ada webhook, signature, idempotensi `qr_ref`, job/event/notification. **Ditunda** sampai gateway nyata ditentukan. |
| Auto-cancel saat batas bayar habis | ❌ Belum | `expires_at` tersimpan dan berhenti memblokir ketersediaan, tapi tidak ada scheduler/`rentals:expire`. Status di DB tidak berubah otomatis. |

### Tambahan Minggu 10

| Permintaan | Status awal |
|---|---|
| FRONTEND: kolom foto SIM di data diri | ❌ Belum |
| FRONTEND: halaman data diri menampilkan foto yang sudah diupload | ❌ Belum (semua input selalu kosong, wajib upload ulang) |
| BACKEND: modul/konfirmasi denda di detail transaksi admin | ⚠️ Modal denda sudah ada, tetapi masih meminta nominal manual |
| BACKEND: tempat foto SIM di dokumen verifikasi admin | ❌ Belum (hanya 2 dokumen: KTP, selfie) |
| BACKEND: sistem foto SIM terintegrasi di data diri | ❌ Belum |

---

## 2. Keputusan yang sudah disetujui

1. **Dokumen acuan diperbarui** untuk SIM dan untuk perubahan aturan denda.
2. **SIM wajib**, validasi seperti KTP (`image`, `jpg/jpeg/png/webp`, maks. 2048 KB), disimpan di disk **private**, dan ditampilkan di dokumen verifikasi admin.
3. **Denda dihitung otomatis**, bukan diisi manual:
   - `late_fee = jam_keterlambatan_bulatan_atas × 6% × rental.price_per_day`
   - contoh: `price_per_day = 500.000` → `30.000` per jam; 30 menit → 1 jam → `30.000`.
   - dasar harga: **`rental.price_per_day`** (snapshot saat pemesanan), bukan `car.harga_sewa` terkini.
   - nominal akhir dibulatkan ke rupiah bulat (integer).
4. Modal konfirmasi denda:
   - **Ya** → `late_fee > 0`, payment `type=Denda`, `method=Tunai`, status `Berhasil`, rental `Selesai` + catatan riwayat "Dikembalikan terlambat".
   - **Tidak** → `Selesai` bersih, `late_fee = 0`, **tanpa payment denda**.
   - Penandaan terlambat memakai `late_fee` + `booking_history`, bukan status baru.
5. **Webhook QRIS ditunda** sampai penyedia gateway dan format signature ditentukan.
6. `docs/spesifikasi_laravel_gorental.md` tetap tidak disentuh.

---

## 3. Peta kepingan kerja (puzzle)

Urutan dependency: **P0 → (P1, P2, P3, P4) → P5**. P1–P4 bisa dikerjakan paralel setelah P0 selesai.

### P0 — Dokumen acuan (blocker)

| ID | Kepingan | Acceptance |
|---|---|---|
| P0.1 | `usecase_gorental.md` C-12 (`:222`, `:226`): tambahkan **foto SIM** sebagai field wajib, termasuk alternatif validasi. | Daftar field C-12 = nama, HP, NIK, KTP, swafoto, SIM. |
| P0.2 | `usecase_gorental.md` §3.7 (`:140-144`): SIM masuk data pribadi, disk private, route terproteksi. | Tidak ada lagi teks yang menyebut hanya KTP/selfie. |
| P0.3 | `usecase_gorental.md` F006 (`:252`, `:258`): verifikasi dokumen mencakup SIM. | Tunai dan QRIS sama-sama menyebut KTP/selfie/SIM. |
| P0.4 | `usecase_gorental.md` F008 (`:302-307`): ganti "nominal diisi manual" dengan **hitung otomatis** + modal konfirmasi Ya/Tidak. | Rumus 6%, pembulatan jam ke atas, cabang Ya/Tidak, dan penandaan via `late_fee` + `booking_history` tertulis. |
| P0.5 | `usecase_gorental.md` §6 (`:335`): hapus "Perhitungan denda otomatis" dari daftar di luar lingkup. | Tidak ada kontradiksi dengan P0.4. |
| P0.6 | `database_gorental.md` `rental` (`:138-139`): tambah `customer_sim_photo varchar(255)` not null, path disk private. | Kolom ditambahkan di dekat KTP/selfie; `:78` tetap melarang kolom identitas di `account`. |
| P0.7 | `database_gorental.md` §7 (`:235-244`): tambah `rental.late_fee_rate_per_hour = 0.06` dan aturan pembulatan. | Config key tertulis dan siap ditambah ke `config/rental.php`. |
| P0.8 | `database_gorental.md` §6 (`:216-231`) & §8: langkah **migration baru** dan seeder menyertakan SIM. | Tidak ada pengubahan migration lama (`:15`). |

### P1 — A-08 input kode tunai

| ID | Kepingan | Acceptance |
|---|---|---|
| P1.1 | Route `POST /admin/transaksi/{rental}/verifikasi-tunai` dengan middleware `auth, admin`, nama `admin.transaksi.verifikasi-tunai`. | Muncul di `route:list`. |
| P1.2 | FormRequest: `payment_code` wajib, format `TNI-XXXX-XXXX`. | Invalid → error field. |
| P1.3 | Controller: cari `cash.payment_code` yang masih `Sewa`/`Tunai`/`Menunggu Pembayaran` dan `expires_at > now()`. | Kode salah → "Kode pembayaran tidak ditemukan."; kode lewat waktu → ditolak. |
| P1.4 | Dalam `DB::transaction`: payment → `Menunggu Konfirmasi`, rental → `Menunggu Verifikasi` lewat `Rental::transitionTo()`. | `booking_history` bertambah; status lain tidak berubah. |
| P1.5 | Form input kode di halaman `transaksi.blade.php` tab Tunai (A-08). | Admin bisa memasukkan kode tanpa membuka detail. |
| P1.6 | Test: kode valid, kode salah, kode kedaluwarsa, akses non-admin. | Semua cabang teruji. |

### P2 — Scheduler (auto-cancel & status berjalan)

| ID | Kepingan | Acceptance |
|---|---|---|
| P2.1 | Command `rentals:expire` (tiap menit): rental `Menunggu Pembayaran`/`Menunggu Verifikasi` dengan payment `Sewa` masih `Menunggu Pembayaran` dan `payment.expires_at <= now()` → payment `Kadaluarsa`, rental → `Dibatalkan` lewat `transitionTo()`. Payment `Menunggu Konfirmasi`/`Berhasil` dilewati. | Test `travelTo()` membuktikan status & payment berubah, dan payment `Menunggu Konfirmasi` aman. |
| P2.2 | Command `rentals:tick` (tiap menit): `Pembayaran Berhasil` + `start_date` tiba → `Berjalan` (mobil `Disewa`); `Berjalan` + lewat `end_date` → `Terlambat`. | Test transisi dan mobil `Disewa`. |
| P2.3 | Command `mobils:purge-inactive` (harian): soft delete mobil `Nonaktif` yang sudah tidak punya rental pemblokir. | Test tidak menghapus mobil yang masih dipesan. |
| P2.4 | Daftarkan ketiganya di `bootstrap/app.php` via `->withSchedule(...)`. | `php artisan schedule:list` menampilkan jadwal. |
| P2.5 | Test command via `artisan()` + `travelTo()`. | Tidak ada perubahan status di luar `transitionTo()`. |
| P2.6 | Webhook QRIS **tidak dibuat**. Expiry QRIS ditangani P2.1. | Tidak ada route webhook/signature sebelum gateway ditentukan. |

### P3 — Denda otomatis + modal konfirmasi

| ID | Kepingan | Acceptance |
|---|---|---|
| P3.1 | Tambah `rental.late_fee_rate_per_hour => 0.06` di `config/rental.php`. | Angka bisa dioverride env bila perlu. |
| P3.2 | Helper hitung denda: `jam = ceil(menit_keterlambatan / 60)`, `late_fee = round(jam × 0.06 × rental.price_per_day)`. | 30 menit → 1 jam; 500.000 → 30.000. |
| P3.3 | `showTransaksi()` mengirim `$terlambat` dan `$dendaHitung` ke `detail-transaksi.blade.php`; modal menampilkan nominal hitungan, bukan input bebas. | Nominal tampil sebelum konfirmasi. |
| P3.4 | Modal punya dua aksi: **Ya** dan **Tidak**. Server **menghitung ulang** denda; tidak mempercayai nominal dari client. | Tidak ada `late_fee` dari POST yang dipakai langsung. |
| P3.5 | `selesaikan()`: Ya → `late_fee`, payment `Denda`/`Tunai`/`Berhasil`, `Selesai` + history "Dikembalikan terlambat"; Tidak → `late_fee = 0`, tanpa payment, `Selesai` bersih. | Kedua cabang teruji; tepat waktu tetap tanpa denda. |
| P3.6 | Validasi `konfirmasi_denda` wajib hanya saat terlambat. | POST tanpa konfirmasi → error; status tidak berubah. |
| P3.7 | Tampilkan `late_fee` dan payment `Denda` di kartu pembayaran detail transaksi admin. | Denda terlihat tanpa membuka riwayat. |

### P4 — SIM end-to-end

| ID | Kepingan | Acceptance |
|---|---|---|
| P4.1 | Migration baru `add_customer_sim_photo_to_rental_table` (jangan edit migration lama). | Kolom `customer_sim_photo` ada di DB. |
| P4.2 | `Rental::$fillable` memasukkan `customer_sim_photo`. | Path tersimpan saat ringkasan dibuat. |
| P4.3 | `DataDiriRequest`: `foto_sim` wajib, `image|mimes:jpg,jpeg,png,webp|max:2048`. | Invalid → error field, tidak lanjut. |
| P4.4 | `dataDiriStore()`: simpan `verifikasi/sim` di disk `local`, hapus foto draft lama, simpan path di draft. | File privat; NIK tetap terenkripsi. |
| P4.5 | `ringkasanStore()`: salin path SIM ke `rental.customer_sim_photo`. | Kolom terisi. |
| P4.6 | `dataDiri()` mengirim path existing (KTP/selfie/SIM) dari draft; Blade menampilkan **preview** foto yang sudah diunggah. | Buka ulang data diri → foto tampil, tidak perlu upload ulang. |
| P4.7 | Route dokumen `whereIn('jenis', ['ktp','selfie','sim'])` + cabang `AdminController::dokumen()`. | SIM bisa dibuka hanya oleh admin/pemilik. |
| P4.8 | `detail-transaksi.blade.php`: 3 slot dokumen, teks "3 dokumen diterima". | Tampil berdampingan dengan KTP/selfie. |
| P4.9 | Factory/seeder menyertakan path SIM. | `migrate:fresh --seed` tidak gagal karena field wajib. |
| P4.10 | Test: upload SIM, validasi, preview saat revisit, 3 dokumen di admin, akses route dokumen. | Semua cabang teruji. |

### P5 — Verifikasi akhir

| ID | Kepingan | Acceptance |
|---|---|---|
| P5.1 | `php artisan test --compact tests/Feature/PemesananMobilTest.php tests/Feature/TransaksiFilterDanRiwayatTest.php tests/Feature/DokumenVerifikasiTest.php tests/Feature/RentalStatusTest.php` | Lulus. |
| P5.2 | `php artisan test --compact` | Seluruh suite lulus. |
| P5.3 | `vendor/bin/pint --dirty --format agent` | Bersih. |
| P5.4 | `php artisan migrate:fresh --seed --force` pada `pbl-113-coba` | Berhasil. |
| P5.5 | `php artisan route:list --except-vendor` & `php artisan schedule:list` | Route/scheduler baru muncul. |
| P5.6 | Cek browser: data diri → SIM & preview; tunai → input kode admin; lewat waktu → auto-cancel; terlambat → modal denda Ya/Tidak; dokumen admin 3 foto. | Alur berjalan dari UI. |

---

## 4. Jebakan yang harus dihindari

- **Migration yang sudah dijalankan tidak boleh diubah**; semua perubahan skema pakai migration baru (`database_gorental.md:15`).
- **Perubahan `rental.status` hanya lewat `Rental::transitionTo()`** (`usecase_gorental.md:39`); scheduler/admin tidak boleh `update(['status' => ...])` langsung.
- **Payment `Denda` tidak punya baris `cash` maupun `qris`** (`database_gorental.md:58`).
- **Jangan menambah kolom identitas ke `account`** (`database_gorental.md:78`); SIM mengikuti KTP/selfie di `rental`.
- **Foto KTP/selfie/SIM di disk private** (`config/filesystems.php`, disk `local` → `storage/app/private`); jangan pakai disk `public`.
- **NIK tetap `encrypted`** (`Rental.php`); jangan pernah simpan plaintext.
- **Semua uang integer rupiah**; tidak pakai decimal/float.
- **Jangan percaya nominal denda dari request**; hitung ulang di server dari `rental.price_per_day`.
- **QRIS tidak diubah** sampai gateway nyata ada; jangan menambah webhook simulasi yang mengubah status.
- Test memakai SQLite; untuk bug spesifik MySQL/identifier, jalankan juga alur terkait pada DB `pbl-113-coba`.

---

## 5. Definition of Done Minggu 10

- [ ] P0: kedua dokumen acuan sudah konsisten dengan SIM dan denda otomatis.
- [ ] P1: admin bisa memasukkan kode tunai dan status berpindah ke `Menunggu Verifikasi`.
- [ ] P2: scheduler auto-cancel, tick, dan purge aktif serta teruji.
- [ ] P3: denda dihitung otomatis; modal Ya/Tidak berfungsi; denda tampil di detail admin.
- [ ] P4: SIM wajib di data diri, preview foto berfungsi, 3 dokumen tampil di admin.
- [ ] P5: seluruh verifikasi lulus dan alur browser sudah dicek.
