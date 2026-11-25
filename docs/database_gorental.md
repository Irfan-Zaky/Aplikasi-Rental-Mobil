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
4. Nama tabel mengikuti rancangan: tunggal dan huruf kecil (`account`, `car`, `car_photo`, `rental`, `add_on`, `payment`, `qris`, `cash`, `booking_history`). PK juga mengikuti rancangan (`account_id`, `car_id`, `photo_id`, `rental_id`, `payment_id`, `history_id`), bukan `id`.
5. `rental.customer_nik` memakai cast `encrypted`. Foto KTP dan selfie disimpan di disk private.
6. FK ke `account` dan `car` dari `rental`: `restrictOnDelete` (riwayat tidak boleh hilang). `car` memakai soft delete.
7. Perubahan `rental.status` hanya lewat `Rental::transitionTo()` yang menulis `booking_history`.
8. Setiap FK dan kolom yang dipakai untuk pencarian/filter harus ber-index (lihat tiap tabel).

---

## 2. Nilai Enum

| Enum | Kolom | Nilai |
|---|---|---|
| `Role` | `account.role` | `admin`, `user` |
| `CarStatus` | `car.status` | `Tersedia`, `Disewa`, `Perbaikan`, `Nonaktif` |
| `RentalStatus` | `rental.status`, `booking_history.status` | `Menunggu Pembayaran`, `Menunggu Verifikasi`, `Pembayaran Berhasil`, `Berjalan`, `Terlambat`, `Selesai`, `Dibatalkan` |
| `PaymentStatus` | `payment.status` | `Menunggu Pembayaran`, `Menunggu Konfirmasi`, `Berhasil`, `Gagal`, `Kadaluarsa` |
| `PaymentMethod` | `payment.method` | `QRIS`, `Tunai` |
| `PaymentType` | `payment.type` | `Sewa`, `Denda` |
| `HandoverMethod` | `add_on.handover_method` | `Diantar`, `Ambil di garasi` |

Ejaan baku `Kadaluarsa` (bukan Kedaluwarsa).

---

## 3. Relasi

```text
account         1───* rental       *───1 car
car             1───* car_photo
rental          1───1 add_on
rental          1───* payment
payment         1───0..1 qris      (method = QRIS,  type = Sewa)
payment         1───0..1 cash      (method = Tunai, type = Sewa)
rental          1───* booking_history
payment         *───0..1 account   (confirmed_by)
booking_history *───0..1 account   (changed_by)
```

- Satu rental boleh punya banyak payment (percobaan ulang QRIS, pembayaran denda), tetapi hanya satu payment `Sewa` aktif (`Menunggu Pembayaran` / `Menunggu Konfirmasi`) pada satu waktu.
- `qris` dan `cash` saling eksklusif untuk satu payment.
- Payment `type = Denda` tidak punya baris di `qris` maupun `cash`.

---

## 4. Tabel yang Sudah Ada

### account

| Kolom | Tipe | Keterangan |
|---|---|---|
| account_id | bigint PK | |
| name | varchar | Nama lengkap |
| email | varchar, unique | |
| password | varchar(255) | Hash, cast `hashed` |
| role | string | `admin` / `user` |
| no_hp | varchar(15), null, **unique** | **Perubahan**. Wajib diisi saat registrasi customer |
| username | varchar(50), null, **unique** | **Perubahan**. Dipakai admin |
| photo_profil | varchar(255), null | **Perubahan** |
| timestamps | | |

**Jangan** menambah kolom `nik`, `ktp_path`, `selfie_path`, `verification_status`, `rejection_note` ke `account`. Data identitas hanya di `rental`.

Login memakai satu input yang dicocokkan ke `email`, `no_hp`, atau `username`.

### car

| Kolom | Tipe | Keterangan |
|---|---|---|
| car_id | bigint PK | |
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

### car_photo

| Kolom | Tipe | Keterangan |
|---|---|---|
| photo_id | bigint PK | |
| car_id | FK car.car_id, cascade on delete | |
| path_foto | varchar | Disk `public` |
| is_utama | boolean | Foto utama katalog |
| timestamps | | |

Nama tabel dan PK mengikuti rancangan (`car_photo`, `photo_id`).

---

## 5. Tabel Baru

### rental

| Kolom | Tipe | Null | Keterangan |
|---|---|---|---|
| rental_id | bigint PK | | |
| trx_code | varchar(20) | tidak | **unique**. Format `TRX-…` |
| booking_code | varchar(20) | ya | **unique**. Format `BK-XXXXXX`, diisi saat konfirmasi pembayaran |
| invoice_number | varchar(30) | ya | **unique**. Format `INV-…` |
| invoice_issued_at | timestamp | ya | |
| account_id | FK account.account_id | tidak | restrictOnDelete |
| car_id | FK car.car_id | tidak | restrictOnDelete |
| start_date | datetime | tidak | Tanggal mulai pukul 00:00:00 |
| end_date | datetime | tidak | Tanggal selesai pukul 23:59:59 |
| duration | smallint unsigned | tidak | Jumlah hari kalender inklusif |
| price_per_day | unsignedInteger | tidak | Snapshot `car.harga_sewa` |
| subtotal | unsignedInteger | tidak | `price_per_day × duration` |
| total_rental | unsignedInteger | tidak | `subtotal + add_on.driver_fee` |
| late_fee | unsignedInteger | tidak | Default 0. Denda, di luar `total_rental` |
| self_driver | boolean | tidak | `1` lepas kunci, `0` dengan sopir |
| customer_name | varchar(100) | tidak | |
| customer_nik | text | tidak | Cast `encrypted`. NIK asli 16 digit |
| customer_phone | varchar(15) | tidak | |
| customer_ktp_photo | varchar(255) | tidak | Path disk private |
| customer_sim_photo | varchar(255) | tidak | Path disk private. Baris lama tanpa SIM memakai string kosong |
| customer_selfie_photo | varchar(255) | tidak | Path disk private |
| terms_accepted_at | timestamp | ya | Waktu menyetujui syarat dan ketentuan |
| status | varchar(30) | tidak | Default `Menunggu Pembayaran`. **Index** |
| expires_at | datetime | ya | Batas rental belum dibayar |
| returned_at | datetime | ya | Waktu mobil dikembalikan |
| cancelled_at | datetime | ya | |
| cancel_reason | varchar(255) | ya | Wajib saat admin membatalkan |
| timestamps | | | |

Index: `(car_id, start_date, end_date)`, `(account_id, status)`, `(status)`.

### add_on

| Kolom | Tipe | Null | Keterangan |
|---|---|---|---|
| rental_id | PK + FK rental | tidak | cascadeOnDelete. Relasi 1:1 |
| handover_method | varchar(20) | ya | `Diantar` / `Ambil di garasi`. Null bila dengan sopir |
| pickup_point | varchar(255) | ya | Alamat antar/jemput. Null bila `Ambil di garasi` |
| driver_fee | unsignedInteger | tidak | Default 0. `0` bila lepas kunci |
| driver_name | varchar(100) | ya | Diisi admin bila dengan sopir |
| driver_phone | varchar(15) | ya | Tampil di invoice |
| timestamps | | | |

Satu baris dibuat untuk **setiap** rental.

### payment

| Kolom | Tipe | Null | Keterangan |
|---|---|---|---|
| payment_id | bigint PK | | |
| rental_id | FK rental.rental_id | tidak | restrictOnDelete |
| type | varchar(10) | tidak | Default `Sewa` |
| method | varchar(10) | tidak | `QRIS` / `Tunai` |
| amount | unsignedInteger | tidak | Rupiah. Sewa = `total_rental`. Denda = `late_fee` |
| status | varchar(30) | tidak | Default `Menunggu Pembayaran` |
| expires_at | datetime | ya | QRIS = dibuat + 10 menit. Tunai = dibuat + 3 jam. Denda = null |
| paid_at | datetime | ya | Waktu pembayaran |
| confirmed_by | FK account.account_id | ya | nullOnDelete. Admin yang mengonfirmasi |
| confirmed_at | datetime | ya | |
| timestamps | | | |

Index: `(rental_id, status)`, `(status, expires_at)`.

### qris

| Kolom | Tipe | Null | Keterangan |
|---|---|---|---|
| payment_id | PK + FK payment.payment_id | tidak | cascadeOnDelete |
| qr_ref | varchar(100) | tidak | **unique**. Referensi gateway, kunci idempotensi webhook |
| qr_code | text | tidak | Payload/link QR |
| raw_payload | json | ya | Respons terakhir gateway |
| timestamps | | | |

`qr_ref` adalah `order_id` Midtrans dan kunci idempotensi webhook. `qr_code` menyimpan URL QR dari `actions[name=generate-qr-code].url`. `raw_payload` menyimpan respons gateway terakhir. **Jangan** menambah kolom `qr_url`, `deeplink_url`, `midtrans_transaction_id`, `midtrans_payment_type`, atau `midtrans_response`.

### cash

| Kolom | Tipe | Null | Keterangan |
|---|---|---|---|
| payment_id | PK + FK payment.payment_id | tidak | cascadeOnDelete |
| payment_code | varchar(50) | tidak | **unique**. Format `TNI-XXXX-XXXX` |
| timestamps | | | |

### booking_history

| Kolom | Tipe | Null | Keterangan |
|---|---|---|---|
| history_id | bigint PK | | |
| rental_id | FK rental.rental_id | tidak | restrictOnDelete |
| sequence_no | smallint unsigned | tidak | Mulai 1, naik per perubahan status |
| status | varchar(30) | tidak | Nilai `RentalStatus` |
| changed_by | FK account.account_id | ya | nullOnDelete. Null bila diubah sistem/scheduler |
| note | varchar(255) | ya | |
| changed_at | timestamp | tidak | Default sekarang |

Unique: `(rental_id, sequence_no)`. `sequence_no` diisi `max + 1` di dalam transaksi yang sama dengan perubahan status. Tabel ini **append-only**: tidak ada update atau delete.

---

## 6. Urutan Migration

Tabel transaksi punya FK, urutannya tidak boleh dibalik. Implementasi sudah memakai nama tabel dan PK di atas; untuk instalasi baru jalankan `php artisan migrate:fresh --seed`. Tabel `brand` dan `car_type` tidak dibuat; `car.merk` dan `car.jenis` tetap berupa kolom string.

| No | Isi | Jenis |
|---|---|---|
| 1 | `account`: tambah `no_hp` (unique), `username` (unique), `photo_profil` | Ubah tabel |
| 2 | `car`: `status` → string(20), petakan `Perawatan` → `Perbaikan`, tambah `deleted_at` | Ubah tabel |
| 3 | `rental` | Baru |
| 4 | `add_on` | Baru |
| 5 | `payment` | Baru |
| 6 | `qris` | Baru |
| 7 | `cash` | Baru |
| 8 | `booking_history` | Baru |
| 9 | `rental`: tambah `customer_sim_photo` | Ubah tabel (migration baru) |

Jangan membuat migration untuk kolom verifikasi di `account` (lihat bagian 4).

---

## 7. Konfigurasi yang Dipakai Data

| Key | Dipakai untuk |
|---|---|
| `rental.driver_fee_per_day` | `add_on.driver_fee = nilai × duration` bila dengan sopir |
| `rental.contact_phone` | Kontak admin di invoice bila tanpa sopir |
| `rental.pending_ttl_minutes` (30) | `rental.expires_at` saat rental dibuat, sebelum metode bayar dipilih |
| `rental.qris_ttl_minutes` (10) | `payment.expires_at` QRIS dan `custom_expiry` Midtrans |
| `rental.cash_ttl_hours` (3) | `payment.expires_at` tunai |
| `rental.late_fee_rate_per_hour` (0.06) | Denda otomatis: `ceil(menit/60) × rate × price_per_day` |
| `midtrans.is_production`, `midtrans.server_key`, `midtrans.client_key`, `midtrans.signature_secret_key`, `midtrans.base_url` | Midtrans Core API; Server Key untuk API, Signature Secret Key untuk verifikasi notifikasi; tidak ada kolom tambahan di `qris` |

---

## 8. Seeder

- Akun admin: `role = admin`, `username` terisi, password di-hash. Tidak ada fitur pendaftaran admin.
- Contoh mobil dan foto untuk pengembangan.
- Contoh rental menyertakan path dokumen verifikasi KTP, SIM, dan selfie di disk private.
