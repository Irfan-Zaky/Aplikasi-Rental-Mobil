# Database GoRental

Acuan skema database untuk AI agent. Baca dokumen ini setiap kali pekerjaan menyentuh tabel, kolom, migration, model, relasi, query, seeder, atau factory.

**Aturan membaca**

- Dokumen ini adalah acuan **tunggal** untuk nama tabel, nama kolom, tipe, dan nilai enum.
- Jangan membuat tabel atau kolom di luar dokumen ini tanpa instruksi pengguna.
- Perilaku bisnis (kapan status berubah, aturan ketersediaan, dll.) ada di `usecase_gorental.md`.

---

## 1. Aturan Umum

1. Migration yang **sudah dijalankan** tidak boleh diubah. Perubahan skema = migration baru.
2. Semua enum disimpan sebagai `string` (bukan enum database) dan divalidasi lewat PHP Backed Enum.
3. Semua nilai uang adalah rupiah bulat: `unsignedInteger`. Jangan memakai decimal/float.
4. Tabel lama (`users`, `mobils`, `mobil_fotos`) tidak di-rename. Tabel baru memakai nama kolom Inggris seperti di bawah, kecuali FK ke tabel lama: `user_id` dan `mobil_id`.
5. `rentals.customer_nik` memakai cast `encrypted`. Foto KTP dan selfie disimpan di disk private.
6. FK ke `users` dan `mobils` dari `rentals`: `restrictOnDelete` (riwayat tidak boleh hilang). `mobils` memakai soft delete.
7. Perubahan `rentals.status` hanya lewat `Rental::transitionTo()` yang menulis `booking_histories`.
8. Setiap FK dan kolom yang dipakai untuk pencarian/filter harus ber-index (lihat tiap tabel).

---

## 2. Nilai Enum

| Enum | Kolom | Nilai |
|---|---|---|
| `Role` | `users.role` | `admin`, `user` |
| `MobilStatus` | `mobils.status` | `Tersedia`, `Disewa`, `Perbaikan`, `Nonaktif` |
| `RentalStatus` | `rentals.status`, `booking_histories.status` | `Menunggu Pembayaran`, `Menunggu Verifikasi`, `Pembayaran Berhasil`, `Berjalan`, `Terlambat`, `Selesai`, `Dibatalkan` |
| `PaymentStatus` | `payments.status` | `Menunggu Pembayaran`, `Menunggu Konfirmasi`, `Berhasil`, `Gagal`, `Kadaluarsa` |
| `PaymentMethod` | `payments.method` | `QRIS`, `Tunai` |
| `PaymentType` | `payments.type` | `Sewa`, `Denda` |
| `HandoverMethod` | `rental_add_ons.handover_method` | `Diantar`, `Ambil di garasi` |

Ejaan baku `Kadaluarsa` (bukan Kedaluwarsa).

---

## 3. Relasi

```text
users      1───* rentals       *───1 mobils
mobils     1───* mobil_fotos
rentals    1───1 rental_add_ons
rentals    1───* payments
payments   1───0..1 qris_payments    (method = QRIS,  type = Sewa)
payments   1───0..1 cash_payments    (method = Tunai, type = Sewa)
rentals    1───* booking_histories
payments   *───0..1 users            (confirmed_by)
booking_histories *───0..1 users     (changed_by)
```

- Satu rental boleh punya banyak payment (percobaan ulang QRIS, pembayaran denda), tetapi hanya satu payment `Sewa` aktif (`Menunggu Pembayaran` / `Menunggu Konfirmasi`) pada satu waktu.
- `qris_payments` dan `cash_payments` saling eksklusif untuk satu payment.
- Payment `type = Denda` tidak punya baris di `qris_payments` maupun `cash_payments`.

---

## 4. Tabel yang Sudah Ada

### users

| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| name | varchar | Nama lengkap |
| email | varchar, unique | |
| password | varchar(255) | Hash, cast `hashed` |
| role | string | `admin` / `user` |
| no_hp | varchar(15), null, **unique** | **Perubahan**. Wajib diisi saat registrasi customer |
| username | varchar(50), null, **unique** | **Perubahan**. Dipakai admin |
| photo_profil | varchar(255), null | **Perubahan** |
| timestamps | | |

**Jangan** menambah kolom `nik`, `ktp_path`, `selfie_path`, `verification_status`, `rejection_note` ke `users`. Data identitas hanya di `rentals`.

Login memakai satu input yang dicocokkan ke `email`, `no_hp`, atau `username`.

### mobils

| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| kode_mobil | varchar | |
| merk | varchar | Merek (menggantikan tabel brand) |
| nama_mobil | varchar | Model/nama |
| jenis | varchar | Jenis (menggantikan tabel car_type) |
| nomor_polisi | varchar(15), unique | |
| harga_sewa | unsignedInteger | Harga per hari |
| spesifikasi | text | |
| status | varchar(20), default `Tersedia` | **Perubahan**: sebelumnya enum 3 nilai. Data `Perawatan` dipetakan ke `Perbaikan` |
| deleted_at | timestamp, null | **Perubahan**: soft delete |
| timestamps | | |

Index tambahan yang disarankan: `(merk)`, `(jenis)`, `(status)`.

### mobil_fotos

| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| mobil_id | FK mobils, cascade on delete | |
| path_foto | varchar | Disk `public` |
| is_utama | boolean | Foto utama katalog |
| timestamps | | |

Tidak ada perubahan skema.

---

## 5. Tabel Baru

### rentals

| Kolom | Tipe | Null | Keterangan |
|---|---|---|---|
| id | bigint PK | | |
| trx_code | varchar(20) | tidak | **unique**. Format `TRX-…` |
| booking_code | varchar(20) | ya | **unique**. Format `BK-XXXXXX`, diisi saat konfirmasi pembayaran |
| invoice_number | varchar(30) | ya | **unique**. Format `INV-…` |
| invoice_issued_at | timestamp | ya | |
| user_id | FK users | tidak | restrictOnDelete |
| mobil_id | FK mobils | tidak | restrictOnDelete |
| start_date | datetime | tidak | Tanggal mulai pukul 00:00:00 |
| end_date | datetime | tidak | Tanggal selesai pukul 23:59:59 |
| duration | smallint unsigned | tidak | Jumlah hari kalender inklusif |
| price_per_day | unsignedInteger | tidak | Snapshot `mobils.harga_sewa` |
| subtotal | unsignedInteger | tidak | `price_per_day × duration` |
| total_rental | unsignedInteger | tidak | `subtotal + rental_add_ons.driver_fee` |
| late_fee | unsignedInteger | tidak | Default 0. Denda, di luar `total_rental` |
| self_driver | boolean | tidak | `1` lepas kunci, `0` dengan sopir |
| customer_name | varchar(100) | tidak | |
| customer_nik | text | tidak | Cast `encrypted`. NIK asli 16 digit |
| customer_phone | varchar(15) | tidak | |
| customer_ktp_photo | varchar(255) | tidak | Path disk private |
| customer_selfie_photo | varchar(255) | tidak | Path disk private |
| terms_accepted_at | timestamp | ya | Waktu menyetujui syarat dan ketentuan |
| status | varchar(30) | tidak | Default `Menunggu Pembayaran`. **Index** |
| expires_at | datetime | ya | Batas rental belum dibayar |
| returned_at | datetime | ya | Waktu mobil dikembalikan |
| cancelled_at | datetime | ya | |
| cancel_reason | varchar(255) | ya | Wajib saat admin membatalkan |
| timestamps | | | |

Index: `(mobil_id, start_date, end_date)`, `(user_id, status)`, `(status)`.

### rental_add_ons

| Kolom | Tipe | Null | Keterangan |
|---|---|---|---|
| rental_id | PK + FK rentals | tidak | cascadeOnDelete. Relasi 1:1 |
| handover_method | varchar(20) | ya | `Diantar` / `Ambil di garasi`. Null bila dengan sopir |
| pickup_point | varchar(255) | ya | Alamat antar/jemput. Null bila `Ambil di garasi` |
| driver_fee | unsignedInteger | tidak | Default 0. `0` bila lepas kunci |
| driver_name | varchar(100) | ya | Diisi admin bila dengan sopir |
| driver_phone | varchar(15) | ya | Tampil di invoice |
| timestamps | | | |

Satu baris dibuat untuk **setiap** rental.

### payments

| Kolom | Tipe | Null | Keterangan |
|---|---|---|---|
| id | bigint PK | | |
| rental_id | FK rentals | tidak | restrictOnDelete |
| type | varchar(10) | tidak | Default `Sewa` |
| method | varchar(10) | tidak | `QRIS` / `Tunai` |
| amount | unsignedInteger | tidak | Rupiah. Sewa = `total_rental`. Denda = `late_fee` |
| status | varchar(30) | tidak | Default `Menunggu Pembayaran` |
| expires_at | datetime | ya | QRIS = dibuat + 10 menit. Tunai = dibuat + 3 jam. Denda = null |
| paid_at | datetime | ya | Waktu pembayaran |
| confirmed_by | FK users | ya | nullOnDelete. Admin yang mengonfirmasi |
| confirmed_at | datetime | ya | |
| timestamps | | | |

Index: `(rental_id, status)`, `(status, expires_at)`.

### qris_payments

| Kolom | Tipe | Null | Keterangan |
|---|---|---|---|
| payment_id | PK + FK payments | tidak | cascadeOnDelete |
| qr_ref | varchar(100) | tidak | **unique**. Referensi gateway, kunci idempotensi webhook |
| qr_code | text | tidak | Payload/link QR |
| raw_payload | json | ya | Respons terakhir gateway |
| timestamps | | | |

### cash_payments

| Kolom | Tipe | Null | Keterangan |
|---|---|---|---|
| payment_id | PK + FK payments | tidak | cascadeOnDelete |
| payment_code | varchar(50) | tidak | **unique**. Format `TNI-XXXX-XXXX` |
| timestamps | | | |

### booking_histories

| Kolom | Tipe | Null | Keterangan |
|---|---|---|---|
| id | bigint PK | | |
| rental_id | FK rentals | tidak | restrictOnDelete |
| sequence_no | smallint unsigned | tidak | Mulai 1, naik per perubahan status |
| status | varchar(30) | tidak | Nilai `RentalStatus` |
| changed_by | FK users | ya | nullOnDelete. Null bila diubah sistem/scheduler |
| note | varchar(255) | ya | |
| changed_at | timestamp | tidak | Default sekarang |

Unique: `(rental_id, sequence_no)`. `sequence_no` diisi `max + 1` di dalam transaksi yang sama dengan perubahan status. Tabel ini **append-only**: tidak ada update atau delete.

---

## 6. Urutan Migration

Tabel transaksi punya FK, urutannya tidak boleh dibalik.

| No | Isi | Jenis |
|---|---|---|
| 1 | `users`: tambah `no_hp` (unique), `username` (unique), `photo_profil` | Ubah tabel |
| 2 | `mobils`: `status` → string(20), petakan `Perawatan` → `Perbaikan`, tambah `deleted_at` | Ubah tabel |
| 3 | `rentals` | Baru |
| 4 | `rental_add_ons` | Baru |
| 5 | `payments` | Baru |
| 6 | `qris_payments` | Baru |
| 7 | `cash_payments` | Baru |
| 8 | `booking_histories` | Baru |

Jangan membuat migration untuk kolom verifikasi di `users` (lihat bagian 4).

---

## 7. Konfigurasi yang Dipakai Data

| Key | Dipakai untuk |
|---|---|
| `rental.driver_fee_per_day` | `rental_add_ons.driver_fee = nilai × duration` bila dengan sopir |
| `rental.contact_phone` | Kontak admin di invoice bila tanpa sopir |
| `rental.pending_ttl_minutes` (30) | `rentals.expires_at` saat rental dibuat |
| `rental.qris_ttl_minutes` (10) | `payments.expires_at` QRIS |
| `rental.cash_ttl_hours` (3) | `payments.expires_at` tunai |

---

## 8. Seeder

- Akun admin: `role = admin`, `username` terisi, password di-hash. Tidak ada fitur pendaftaran admin.
- Contoh mobil dan foto untuk pengembangan.
