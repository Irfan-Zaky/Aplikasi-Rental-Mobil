# Use Case GoRental

Acuan perilaku fitur untuk AI agent. Baca **seluruh dokumen** sebelum membuat atau mengubah fitur.

**Aturan membaca**

- Jangan menambah perilaku yang tidak tertulis di sini. Bila ada hal yang tidak tercakup, tanyakan ke pengguna.
- Nama tabel, kolom, dan nilai enum mengikuti `database_gorental.md`. Bila berbeda dengan dokumen ini, `database_gorental.md` yang benar.
- Kode layar (C-xx = customer mobile, A-xx = admin desktop) hanya penanda antarmuka terkait.

---

## 1. Aktor

| Aktor | `account.role` | Tugas |
|---|---|---|
| Customer | `user` | Mencari mobil, memesan, membayar, melihat riwayat. |
| Admin | `admin` | Mengelola mobil, memeriksa dokumen dan pembayaran, menyelesaikan transaksi. Akun dibuat lewat seeder. **Tidak ada pendaftaran admin.** |

Satu model `Account` (tabel `account`), pembeda hanya `role`.

---

## 2. Status

### 2.1 Alur status rental (`rental.status`)

| Dari | Ke | Pemicu | Pelaku |
|---|---|---|---|
| (baru) | `Menunggu Pembayaran` | Customer mengonfirmasi pemesanan (C-13) | Customer |
| `Menunggu Pembayaran` | `Menunggu Verifikasi` | QRIS: webhook Midtrans sukses | Sistem |
| `Menunggu Pembayaran` | `Pembayaran Berhasil` | Tunai: admin menemukan transaksi lewat kode tunai lalu menekan Konfirmasi Pembayaran | Admin |
| `Menunggu Verifikasi` | `Pembayaran Berhasil` | QRIS: admin menekan Konfirmasi Pembayaran | Admin |
| `Pembayaran Berhasil` | `Berjalan` | `start_date` tiba | Scheduler |
| `Berjalan` | `Terlambat` | Lewat `end_date` dan belum dikembalikan | Scheduler |
| `Berjalan` / `Terlambat` | `Selesai` | Admin menekan Selesaikan Transaksi | Admin |
| `Menunggu Pembayaran` / `Menunggu Verifikasi` | `Dibatalkan` | Payment `Sewa` masih `Menunggu Pembayaran` dan melewati `payment.expires_at`; payment `Menunggu Konfirmasi` / `Berhasil` tidak dibatalkan otomatis | Scheduler |
| `Menunggu Pembayaran` / `Menunggu Verifikasi` / `Pembayaran Berhasil` | `Dibatalkan` | Admin menekan Batalkan Pesanan (alasan wajib) | Admin |

- Perubahan status **hanya** lewat satu method terpusat (`Rental::transitionTo()`) yang juga menulis `booking_history`.
- Transisi di luar tabel ini **ditolak**.
- Auto-cancel hanya untuk payment yang benar-benar belum dibayar. Payment `Menunggu Konfirmasi` atau `Berhasil` tidak dibatalkan otomatis meskipun `expires_at` terlewat; pembayaran yang sudah masuk menunggu konfirmasi admin.

### 2.2 Status pembayaran (`payment.status`)

`Menunggu Pembayaran` → `Menunggu Konfirmasi` → `Berhasil`. Cabang: `Gagal`, `Kadaluarsa`. Ejaan baku: **Kadaluarsa**.

### 2.3 Status mobil (`car.status`)

`Tersedia`, `Disewa`, `Perbaikan`, `Nonaktif`.

- Rental menjadi `Berjalan` → mobil `Disewa`.
- Rental `Selesai` → mobil `Tersedia` (hanya bila status saat itu `Disewa`).
- `Perbaikan` dan `Nonaktif` hanya diubah admin (atau aturan F003).

### 2.4 Pengelompokan tampilan

| Grup | Isi |
|---|---|
| Aktif | `Pembayaran Berhasil`, `Berjalan`, `Terlambat` |
| Menunggu | `Menunggu Pembayaran`, `Menunggu Verifikasi` |
| Selesai | `Selesai` |

Dipakai pada ringkasan C-04 dan filter tab C-17. `Dibatalkan` hanya tampil di tab Semua.

---

## 3. Aturan Bisnis

### 3.1 Ketersediaan

Mobil **dapat dipesan** untuk periode `[S, E]` bila **keduanya** terpenuhi:

1. `car.status` bukan `Perbaikan` / `Nonaktif` dan `deleted_at` kosong.
2. Tidak ada rental lain berstatus pemblokir dengan `existing.start_date <= E AND existing.end_date >= S`.

Status pemblokir: `Menunggu Pembayaran` (hanya bila `expires_at > now()`), `Menunggu Verifikasi`, `Pembayaran Berhasil`, `Berjalan`, `Terlambat`.

Status `Disewa` **tidak** memblokir tanggal lain. Hanya informasi badge.

Badge katalog: `Tersedia`, `Disewa`, `Dalam Perbaikan`, `Tidak Tersedia` (Nonaktif). Mobil `Perbaikan` / `Nonaktif` tidak bisa dipesan.

### 3.2 Durasi dan harga

```text
duration     = jumlah hari kalender inklusif   (12–14 Okt = 3 hari)
start_date   = tanggal mulai   pukul 00:00:00
end_date     = tanggal selesai pukul 23:59:59
subtotal     = harga_sewa × duration
driver_fee   = config('rental.driver_fee_per_day') × duration   (0 bila lepas kunci)
total_rental = subtotal + driver_fee
```

- `price_per_day` disalin ke rental sebagai snapshot.
- `late_fee` **tidak** masuk `total_rental`.
- Tanggal mulai tidak boleh di masa lalu. Tanggal selesai tidak boleh sebelum tanggal mulai.
- Semua nilai uang adalah rupiah bulat (integer).

### 3.3 Pembuatan rental (atomik)

Wajib dalam satu `DB::transaction`:

1. `lockForUpdate()` pada baris mobil.
2. Validasi ulang ketersediaan (3.1) di dalam transaksi.
3. Buat rental: status `Menunggu Pembayaran`, `expires_at = now + 30 menit` (berlaku sampai metode bayar dipilih, lalu disamakan dengan `payment.expires_at`), `trx_code`.
4. Buat `add_on`.
5. Tulis `booking_history` urutan 1.

### 3.4 Pembayaran

| | QRIS | Tunai |
|---|---|---|
| Masa berlaku | 10 menit (`payment.expires_at`) | 3 jam (`payment.expires_at`) |
| Detail | baris `qris` | baris `cash` (`payment_code` format `TNI-XXXX-XXXX`) |
| Sukses | Webhook Midtrans → payment `Menunggu Konfirmasi`, rental `Menunggu Verifikasi` | Admin mencari transaksi dengan kode tunai, lalu konfirmasi langsung → payment `Berhasil`, rental `Pembayaran Berhasil` |
| Gagal | Webhook gagal → payment `Gagal`; customer boleh membuat payment baru selama rental belum `Dibatalkan` | — |
| Kedaluwarsa | Payment masih `Menunggu Pembayaran` saat `expires_at` lewat → `Kadaluarsa`, rental `Dibatalkan`. Bila sudah `Menunggu Konfirmasi`, tetap menunggu admin | Sama; kode tidak bisa dipakai lagi setelah payment `Kadaluarsa` |

- Saat payment `Sewa` dibuat, `rental.expires_at` ikut disamakan dengan `payment.expires_at` dalam transaksi yang sama, supaya aturan pemblokir tanggal (3.1) dan auto-cancel (3.6) memakai batas waktu yang sama.
- Satu rental hanya boleh punya **satu payment `Sewa` aktif** (`Menunggu Pembayaran` / `Menunggu Konfirmasi`) pada satu waktu.
- `amount` = `total_rental`.
- Gateway QRIS adalah **Midtrans Core API** dengan `payment_type = gopay`. Ikuti dokumen `AI_RULES_PAYMENT_MIDTRANS.md` untuk charge dan webhook.
- Webhook: `POST /webhooks/qris`, di luar CSRF, wajib verifikasi signature memakai **Signature Secret Key**, lalu ambil status terbaru lewat Get Status Midtrans, dan **idempoten** berdasarkan `qris.qr_ref`. Get Status harus ber-`status_code = 200`, `201` (`201` untuk `pending`), atau `407` (`407` untuk `expire`) dan memiliki `transaction_status`; jika tidak, balas `502` dan jangan ubah status. Dilarang memakai QR simulasi atau `transaction_status` dari notifikasi sebagai fallback.
- Kode tunai `TNI-XXXX-XXXX` hanya dipakai untuk **pencarian transaksi** oleh admin. Tidak ada input verifikasi kode tunai, tidak ada status `Menunggu Konfirmasi` untuk Tunai.

### 3.5 Kode

| Kode | Format | Kolom | Dibuat saat |
|---|---|---|---|
| Transaksi | `TRX-` + angka berurut | `rental.trx_code` | Rental dibuat |
| Booking | `BK-` + 6 karakter alfanumerik huruf besar | `rental.booking_code` | Konfirmasi pembayaran sewa |
| Invoice | `INV-` + tahun + urut | `rental.invoice_number` | Bersamaan dengan kode booking |
| Tunai | `TNI-XXXX-XXXX` | `cash.payment_code` | Customer memilih Tunai |

Semua kode unik. Invoice dibuat dari data rental (halaman/PDF), bukan tabel sendiri. Kontak di invoice: `add_on.driver_phone` bila dengan sopir, selain itu `config('rental.contact_phone')`.

### 3.6 Scheduler

| Perintah | Jadwal | Tugas |
|---|---|---|
| `rentals:expire` | tiap menit | Batalkan rental `Menunggu Pembayaran`/`Menunggu Verifikasi` yang punya payment `Sewa` masih `Menunggu Pembayaran` dengan `payment.expires_at <= now()`; payment itu → `Kadaluarsa`. Payment `Menunggu Konfirmasi`/`Berhasil` dilewati. |
| `rentals:tick` | tiap menit | `Pembayaran Berhasil` → `Berjalan` (mobil → `Disewa`) saat `start_date` tiba. `Berjalan` → `Terlambat` saat lewat `end_date`. |
| `mobils:purge-inactive` | harian | Soft delete mobil `Nonaktif` yang tidak lagi punya rental pemblokir. |

### 3.7 Konfigurasi

| Key | Dipakai untuk |
|---|---|
| `rental.driver_fee_per_day` | Biaya sopir. |
| `rental.contact_phone` | Kontak invoice tanpa sopir. |
| `rental.pending_ttl_minutes` | Batas rental sebelum metode bayar dipilih. |
| `rental.qris_ttl_minutes` | Batas QRIS dan `custom_expiry` Midtrans. |
| `rental.cash_ttl_hours` | Batas pembayaran Tunai. |
| `rental.late_fee_rate_per_hour` | Tarif denda per jam keterlambatan (default `0.06`). |
| `midtrans.is_production`, `midtrans.server_key`, `midtrans.client_key`, `midtrans.signature_secret_key`, `midtrans.base_url` | Integrasi Midtrans Core API. Server Key untuk charge/status; Signature Secret Key untuk verifikasi webhook. |

### 3.8 Keamanan data

- NIK, foto KTP, foto SIM, dan selfie adalah data pribadi. NIK terenkripsi. KTP, SIM, dan selfie di disk **private** dan hanya bisa diakses admin atau pemilik rental lewat route terproteksi.
- Password di-hash. Customer tidak pernah melihat password (tidak ditampilkan di profil).

---

## 4. Use Case

### F001 — Log In dan Daftar Akun

- **Aktor:** Customer, Admin
- **Layar:** C-01, C-02, C-03, C-04, C-19, A-01, A-02
- **Hasil:** pengguna masuk dashboard sesuai role.

**Alur daftar (customer):**

1. Customer memilih Daftar dan mengisi nama lengkap, email, nomor HP, password, konfirmasi password.
2. Sistem memvalidasi lalu menyimpan akun dengan `role = user`.
3. Customer diarahkan ke login atau dashboard customer.

**Alur login (customer dan admin):**

1. Pengguna mengisi **satu field** `login` (email, nomor HP, atau username) dan password.
2. Sistem mencocokkan `login` ke `email`, `no_hp`, atau `username`.
3. Customer → dashboard customer (C-04). Admin → dashboard admin (A-02).
4. Halaman login customer dan admin terpisah. Autentikasi di backend memakai mekanisme yang sama.

**Validasi dan alternatif:**

- Data tidak lengkap, email tidak valid, atau email / nomor HP sudah dipakai → tampilkan pesan kesalahan.
- Password minimal 8 karakter dan harus sama dengan konfirmasi. Bila tidak → minta diperbaiki.
- Kredensial salah → pesan "Email/nomor HP atau password salah." Jangan membedakan penyebabnya.

### F002 — Mencari Mobil

- **Aktor:** Customer
- **Layar:** C-05, C-06
- **Hasil:** customer memilih mobil dan lanjut ke F004.

1. Customer membuka katalog. Sistem menampilkan daftar mobil, foto utama, harga/hari, badge status.
2. Customer mencari dan memfilter berdasarkan **merek, jenis, dan harga sewa**. Opsi filter merek dan jenis diambil dari nilai `DISTINCT` pada `car`.
3. Customer membuka detail: foto, nama, merek, jenis, spesifikasi, harga, status.
4. Customer menekan Pilih Mobil → F004.

**Alternatif:** mobil `Perbaikan` / `Nonaktif` tampil dengan badge terkait dan **tombol Pilih Mobil dinonaktifkan**. Mobil dengan `deleted_at` tidak tampil.

### F003 — Mengelola Katalog Mobil

- **Aktor:** Admin
- **Layar:** A-03, A-04, A-05
- **Hasil:** data mobil tersimpan dan tampil di katalog.

1. Admin membuka Data Mobil (daftar dengan pencarian dan filter merek, jenis, status).
2. Admin memilih Tambah, Edit, atau Hapus.
3. Tambah/Edit: admin mengisi foto, merek, nama mobil, jenis, status, harga/hari, nomor polisi, spesifikasi. Sistem memvalidasi dan menyimpan. Nomor polisi unik.
4. Daftar menampilkan data terbaru.

**Aturan hapus:**

- Mobil tanpa rental berstatus pemblokir → **soft delete** langsung.
- Mobil dengan rental berstatus pemblokir → status diubah ke `Nonaktif` (tidak muncul di katalog, tidak bisa dipesan, data dipertahankan). Setelah semua rental pemblokir selesai, scheduler melakukan soft delete.
- Riwayat transaksi tidak pernah ikut terhapus.

**Alternatif:** data tidak lengkap/tidak valid → tampilkan error, admin memperbaiki.

### F004 — Memesan Mobil

- **Aktor:** Customer
- **Layar:** C-07 sampai C-13
- **Hasil:** rental berstatus `Menunggu Pembayaran`, customer diarahkan ke F005.

Urutan wajib:

1. **C-07 Pilih Tanggal:** customer memilih tanggal mulai dan selesai. Tampilkan durasi dan estimasi biaya (aturan 3.2).
2. **C-08 Cek Ketersediaan:** sistem memeriksa aturan 3.1. Tersedia → Lanjutkan. Tidak tersedia → modal S-01 (Ganti Tanggal / Ganti Mobil).
3. **C-09 Syarat & Ketentuan:** customer wajib mencentang persetujuan. Tanpa centang, tombol Setuju & Lanjut nonaktif (S-02).
4. **C-10 Pilih Layanan:** Dengan Sopir atau Lepas Kunci (`rental.self_driver`).
5. **C-11 Detail Layanan:**
   - Dengan Sopir → alamat/titik penjemputan wajib (`pickup_point`).
   - Lepas Kunci → pilih `Diantar` atau `Ambil di garasi` (`handover_method`). `Diantar` → alamat pengantaran wajib (`pickup_point`). `Ambil di garasi` → tanpa alamat.
   - Alamat tidak valid → minta isi ulang.
6. **C-12 Data Diri:** nama lengkap, nomor HP, NIK (16 digit angka), foto KTP, foto SIM, swafoto. Semua wajib (S-03 bila tidak valid); foto KTP, SIM, dan swafoto berupa gambar `jpg`/`jpeg`/`png`/`webp` maksimal 2048 KB.
7. **C-13 Ringkasan:** tampilkan mobil, tanggal, durasi, layanan, penerimaan, lokasi, data penyewa, subtotal, biaya sopir, total. Customer menekan Konfirmasi Pemesanan.
8. Sistem membuat rental dengan aturan atomik 3.3, lalu mengarahkan ke C-14.

**Alternatif:** data tidak lengkap atau tidak valid → tampilkan field yang harus diperbaiki dan jangan lanjut. Customer menekan Kembali/Edit → data yang sudah diisi tetap terisi.

### F005 — Melakukan Pembayaran

- **Aktor:** Customer
- **Layar:** C-14, C-15, C-16
- **Hasil:** payment tercatat. QRIS: menunggu hasil gateway. Tunai: menunggu admin.

1. **C-14:** tampilkan detail pesanan dan total. Customer memilih QRIS atau Tunai.
2. QRIS → buat payment + `qris`, tampilkan QR, nominal, hitung mundur 10 menit, status, instruksi (C-15).
3. Tunai → buat payment + `cash`, tampilkan kode pembayaran, total, batas 3 jam, instruksi, lokasi garasi (C-16).
4. Aturan status dan kedaluwarsa mengikuti 3.4.
5. C-15 memantau status pembayaran dari database (bukan memanggil Midtrans dari browser). Begitu payment menjadi `Menunggu Konfirmasi` atau `Berhasil`, halaman menampilkan "Pembayaran diterima" dan otomatis mengarahkan customer ke C-04. Status rental tetap `Menunggu Verifikasi` sampai admin mengonfirmasi pada F006; polling tidak mengubah status dan tidak menerbitkan booking/invoice.

**Modal:** S-04 (QRIS gagal: Tutup / Bayar Kembali), S-05 (QRIS kedaluwarsa), S-06 (kode tunai kedaluwarsa). S-05 dan S-06 → Kembali ke Dashboard.

### F006 — Konfirmasi Pembayaran

- **Aktor:** Admin (Customer menerima hasil)
- **Layar:** A-06, A-07, A-08, C-18
- **Hasil:** pembayaran `Berhasil`, kode booking dan invoice terbit.

**Tunai:**

1. Customer datang ke garasi dan menunjukkan kode tunai.
2. Admin mencari transaksi dengan kode tersebut pada A-02/A-08 (kolom pencarian transaksi). Kode tidak ditemukan atau payment sudah `Kadaluarsa` → beri pesan, tidak ada konfirmasi.
3. Admin membuka detail transaksi, memeriksa detail dan dokumen verifikasi (KTP, SIM, selfie).
4. Admin menekan Konfirmasi Pembayaran. Payment langsung dari `Menunggu Pembayaran` menjadi `Berhasil`, rental langsung menjadi `Pembayaran Berhasil`.
5. Tidak ada status `Menunggu Konfirmasi` / `Menunggu Verifikasi` untuk Tunai.

**QRIS:**

1. Midtrans mengirim webhook `settlement` yang tervalidasi ke `POST /webhooks/qris`; backend melakukan Get Status dan payment menjadi `Menunggu Konfirmasi`, rental `Menunggu Verifikasi`.
2. Admin melihat notifikasi pada daftar "Pembayaran Menunggu Konfirmasi" di A-02 / tab QRIS di A-08.
3. Admin membuka detail, memeriksa kesesuaian pembayaran, data, dan dokumen verifikasi (KTP, SIM, selfie).
4. Admin menekan Konfirmasi Pembayaran.

**Saat konfirmasi (satu transaksi database):**

- payment → `Berhasil`; isi `paid_at`, `confirmed_by`, `confirmed_at`.
- rental → `Pembayaran Berhasil`; isi `booking_code`, `invoice_number`, `invoice_issued_at`.
- Tulis `booking_history`.
- Customer melihat kode booking dan invoice di C-18 (hanya setelah langkah ini).

**Alternatif:**

- Data tidak sesuai → admin **tidak** mengonfirmasi. QRIS tetap `Menunggu Verifikasi` / `Menunggu Konfirmasi`; Tunai tetap `Menunggu Pembayaran` / `Menunggu Pembayaran`.
- Admin menekan **Batalkan Pesanan** (alasan wajib → `cancel_reason`, `cancelled_at`) → rental `Dibatalkan`, payment yang belum `Berhasil` → `Kadaluarsa`. Refund di luar lingkup.
- QRIS belum diterima → payment tetap `Menunggu Pembayaran`, admin belum bisa konfirmasi.
- QRIS gagal → payment `Gagal`, **tidak** ada invoice dan kode booking.

### F007 — Melihat Riwayat Pemesanan

- **Aktor:** Customer, Admin
- **Layar:** C-17, C-18, A-09, A-10

**Customer:**

1. Menu Riwayat menampilkan **hanya** rental milik customer: mobil, tanggal, total, status. Tab: Semua / Aktif / Selesai (grup 2.4).
2. Detail (C-18): progres status, data mobil dan layanan, data penyewa, total, metode, tautan lihat pembayaran, kode booking dan invoice (hanya setelah dikonfirmasi admin).
3. Kosong → "Belum ada riwayat pemesanan."

**Admin:**

1. Menu Riwayat menampilkan seluruh transaksi dengan pencarian dan filter: mobil, merek, jenis, tanggal, metode pembayaran, status.
2. Ringkasan pendapatan dihitung dari `payment` berstatus `Berhasil`.
3. Detail (A-10): data transaksi, pembayaran dan status, denda, serta riwayat status dari `booking_history` (nomor, status, waktu).
4. Filter tanpa hasil → "Tidak ada riwayat pemesanan untuk mobil ini."

### F008 — Pengembalian dan Penyelesaian Transaksi

- **Aktor:** Admin (Customer mengembalikan mobil ke garasi)
- **Layar:** A-06, A-07, A-08 (tab Penyelesaian)
- **Hasil:** rental `Selesai`.

1. Customer mengembalikan mobil. Admin memeriksa mobil.
2. Admin mencari transaksi berdasarkan kode booking, lalu menekan Selesaikan Transaksi. Sistem mengisi `returned_at = now()`.
3. Bila `returned_at <= end_date` → rental `Selesai`, mobil `Tersedia` (bila status `Disewa`), `late_fee = 0`.
4. Bila `returned_at > end_date` → tampilkan modal S-09 berisi lama keterlambatan dan nominal denda yang dihitung server. Admin memilih Ya/Tidak:
   - `late_fee = ceil(menit keterlambatan / 60) × config('rental.late_fee_rate_per_hour') × price_per_day`, dibulatkan ke rupiah bulat.
   - Server menghitung ulang nominal saat konfirmasi; nilai dari form tidak dipercaya.
   - Buat payment `type = Denda`, `method = Tunai`, `amount = late_fee`, status `Berhasil`, `confirmed_by` = admin. Tanpa baris `cash`.
   - Rental `Selesai`, mobil `Tersedia` (bila status `Disewa`).

Perhitungan denda otomatis wajib. Admin tidak mengisi nominal manual.

### F009 — Log Out

- **Aktor:** Customer, Admin
- **Layar:** C-19, semua sidebar admin, modal S-10

1. Pengguna memilih Log Out. Sistem menampilkan konfirmasi (S-10).
2. Konfirmasi → sesi diakhiri (invalidate + regenerate token) dan diarahkan ke login: customer ke `/customer/login`, admin ke `/admin/login`.
3. Batal → sesi tetap aktif.
4. Gagal → pesan "Log out gagal, silakan coba lagi."

---

## 5. Dashboard (ringkasan data)

- **C-04 Customer:** nama, ringkasan jumlah pesanan per grup (Aktif / Menunggu / Selesai), pesanan terakhir, navigasi Beranda / Katalog / Riwayat / Profil.
- **A-02 Admin:** total mobil, mobil `Tersedia`, mobil `Disewa`, total pesanan, jumlah menunggu konfirmasi, daftar "Pembayaran Menunggu Konfirmasi" dari data nyata (QRIS `Menunggu Verifikasi` dan Tunai `Menunggu Pembayaran` yang belum kedaluwarsa): kode, customer, mobil, metode, total, status; navigasi Data Mobil / Pesanan / Riwayat / Logout.

---

## 6. Di Luar Lingkup (jangan dibuat tanpa instruksi)

- Pembatalan oleh customer dan refund.
- Pendaftaran akun admin.
- Fitur laporan keuangan terpisah.
- Verifikasi KTP tingkat akun (verifikasi hanya per pemesanan, diperiksa admin saat F006).
- Tabel brand dan tipe mobil terpisah.
- Pembatalan dan refund pembayaran denda.
