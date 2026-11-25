# AI_RULES: Modul Pembayaran GoRental (Tunai + QRIS Midtrans)

> Aturan **wajib** bagi AI/asisten kode saat membangun atau mengubah modul pembayaran GoRental.
> Dokumen ini sudah diselaraskan dengan `usecase_gorental.md` dan `database_gorental.md` (including keputusan alur Tunai dan denda otomatis).

## 0. Urutan acuan (jika ada konflik)

1. `database_gorental.md`: nama tabel, kolom, tipe, dan nilai enum.
2. `usecase_gorental.md`: perilaku bisnis, status, dan transisi.
3. Dokumentasi Midtrans terbaru: parameter/perilaku Core API dan webhook. Jika berbeda dengan file ini, dokumentasi Midtrans menang; beri tahu developer.
4. File ini: aturan teknis dan keamanan.

**Jangan menambah tabel atau kolom baru.** `qris` hanya memiliki `qr_ref`, `qr_code`, dan `raw_payload`.

---

## 1. Konteks

| Item | Nilai |
|---|---|
| Framework | Laravel 13, PHP 8.5 |
| Aktor | `user` (Customer) dan `admin`. Tidak ada role kasir. |
| Metode bayar | `QRIS` (Midtrans Core API) dan `Tunai` |
| Gateway QRIS | Midtrans **Core API**, `payment_type = gopay` |
| Mata uang | IDR bulat (`integer`) |
| Zona waktu | `Asia/Jakarta` |

Kode Midtrans harus terisolasi di `app/Services/Midtrans/`. Controller/Model tidak boleh memanggil HTTP Midtrans langsung. Dilarang memakai QR simulasi `SIMULASI-...`.

---

## 2. Aturan umum

1. `MIDTRANS_SERVER_KEY` hanya dibaca di `config/midtrans.php`; gunakan `config('midtrans.server_key')` untuk Charge/Status/Cancel. `MIDTRANS_SIGNATURE_SECRET_KEY` juga hanya dibaca di `config/midtrans.php`; gunakan `config('midtrans.signature_secret_key')` untuk verifikasi `signature_key` notifikasi/respons Midtrans. Keduanya jangan pernah muncul di Blade, JavaScript, log, response, atau Git. Sejak 28 Oktober 2026, Server Key dan Signature Secret Key adalah dua key terpisah.
2. Semua panggilan Charge/Status/Cancel hanya dari backend, dengan timeout eksplisit.
3. Status payment hanya boleh berubah lewat:
   - webhook Midtrans tervalidasi + Get Status,
   - scheduler `rentals:expire`,
   - aksi admin (Konfirmasi Pembayaran, Batalkan Pesanan, Selesaikan Transaksi untuk denda).
4. Status rental hanya lewat `Rental::transitionTo()`, yang menulis `booking_history`. Dilarang `$rental->update(['status' => ...])` langsung.
5. Setiap perubahan status dibungkus `DB::transaction` + `lockForUpdate()`. Urutan kunci: **rental lalu payment**.
6. Semua perubahan idempoten. Status akhir (`Berhasil`, `Gagal`, `Kadaluarsa`) tidak boleh diturunkan.
7. Gunakan `declare(strict_types=1);` pada file service baru, type hint penuh, dan enum PHP backed sesuai `database_gorental.md`.
8. Tambah package baru hanya dengan izin developer. Gunakan Laravel HTTP Client (`Http::`).
9. Test memakai `Http::fake()` dan `Http::preventStrayRequests()`; tidak ada request ke Midtrans saat test.

---

## 3. Status

### 3.1 Payment

```text
Menunggu Pembayaran → Menunggu Konfirmasi → Berhasil
        ├─→ Gagal
        └─→ Kadaluarsa
```

Ejaan baku: **Kadaluarsa**.

| Dari | Ke | Pemicu |
|---|---|---|
| `Menunggu Pembayaran` | `Menunggu Konfirmasi` | QRIS: webhook `settlement` tervalidasi |
| `Menunggu Pembayaran` | `Berhasil` | Tunai: admin menekan Konfirmasi Pembayaran |
| `Menunggu Pembayaran` | `Gagal` | QRIS: `deny` / `failure` |
| `Menunggu Pembayaran` | `Kadaluarsa` | Scheduler atau webhook `expire` / `cancel` |
| `Menunggu Konfirmasi` | `Berhasil` | QRIS: admin menekan Konfirmasi Pembayaran |
| `Menunggu Konfirmasi` | `Kadaluarsa` | Hanya Batalkan Pesanan admin |

### 3.2 Rental yang disentuh modul ini

| Dari | Ke | Pemicu |
|---|---|---|
| `Menunggu Pembayaran` | `Menunggu Verifikasi` | QRIS webhook `settlement` |
| `Menunggu Pembayaran` | `Pembayaran Berhasil` | Tunai: admin konfirmasi langsung |
| `Menunggu Verifikasi` | `Pembayaran Berhasil` | QRIS: admin konfirmasi |
| `Menunggu Pembayaran` / `Menunggu Verifikasi` | `Dibatalkan` | Scheduler (payment masih `Menunggu Pembayaran` dan lewat `expires_at`) atau admin |
| `Pembayaran Berhasil` | `Dibatalkan` | Admin (alasan wajib) |

Tidak ada status `Menunggu Verifikasi` untuk Tunai.

---

## 4. Alur bisnis

### 4.1 Pembuatan payment

1. Rental harus `Menunggu Pembayaran` dan `expires_at` belum lewat.
2. `amount = rental.total_rental`, berasal dari server. `late_fee` tidak masuk.
3. Dalam satu transaksi: `lockForUpdate()` rental, cek tidak ada payment `Sewa` aktif, buat payment, samakan `rental.expires_at = payment.expires_at`.
4. QRIS = 10 menit; Tunai = 3 jam.
5. Satu rental hanya boleh punya satu payment `Sewa` aktif (`Menunggu Pembayaran` / `Menunggu Konfirmasi`).

### 4.2 Tunai

1. Buat payment `Sewa`/`Tunai`/`Menunggu Pembayaran` + baris `cash` dengan `payment_code` unik `TNI-XXXX-XXXX`.
2. Tidak ada panggilan Midtrans.
3. Customer menampilkan kode di garasi.
4. Admin mencari transaksi memakai kode tersebut pada A-02/A-08. Kode tidak ditemukan atau payment `Kadaluarsa` → ditolak.
5. Admin memeriksa data dan dokumen, lalu menekan **Konfirmasi Pembayaran**.
6. Payment langsung `Berhasil`, rental langsung `Pembayaran Berhasil`. Tidak ada input verifikasi kode, tidak ada `Menunggu Konfirmasi`.

### 4.3 QRIS

1. Buat `order_id` unik maksimal 50 karakter, mis. `TRX-000123-1767225600-AB12CD`. Simpan di `qris.qr_ref`.
2. `POST /v2/charge` dengan `payment_type: gopay`, `gopay.enable_callback: false`, dan `custom_expiry` 10 menit.
3. Ambil `actions[name=generate-qr-code].url`, simpan di `qris.qr_code`, tampilkan sebagai `<img>`.
4. Item details: sewa mobil + biaya sopir bila ada; total harus sama dengan `gross_amount`.
5. Midtrans mengirim webhook `settlement`; backend verifikasi signature dan Get Status, lalu payment `Menunggu Konfirmasi`, rental `Menunggu Verifikasi`.
6. Admin tetap wajib menekan **Konfirmasi Pembayaran**. Webhook bukan akhir.
7. QRIS gagal → payment `Gagal`. Customer boleh mencoba lagi dengan `order_id` baru selama rental belum dibatalkan.
8. C-15 melakukan polling status ke endpoint customer yang hanya membaca `payment` milik rental sendiri. Saat payment menjadi `Menunggu Konfirmasi` atau `Berhasil`, tampilkan "Pembayaran diterima" dan arahkan otomatis ke C-04. Polling dilarang memanggil Midtrans dari browser, mengubah status, atau menerbitkan booking/invoice sebelum konfirmasi admin.

### 4.4 Konfirmasi pembayaran oleh admin

Hanya dalam satu transaksi DB, kunci rental lalu payment:

- QRIS: rental `Menunggu Verifikasi`, payment `Menunggu Konfirmasi`.
- Tunai: rental `Menunggu Pembayaran`, payment `Menunggu Pembayaran` dengan `method = Tunai` dan belum kedaluwarsa.

Lakukan:

- payment → `Berhasil`; isi `paid_at`, `confirmed_by`, `confirmed_at`;
- rental → `Pembayaran Berhasil` via `transitionTo()`;
- isi `booking_code` (`BK-XXXXXX`), `invoice_number` (`INV-{tahun}-{urut}`), `invoice_issued_at`;
- tulis `booking_history`;
- kode booking/invoice hanya tampil di C-18 setelah langkah ini.

### 4.5 Pembatalan

- Alasan wajib → `cancel_reason`, `cancelled_at`.
- Rental → `Dibatalkan` via `transitionTo()`; payment belum `Berhasil` → `Kadaluarsa`.
- Tidak ada refund.

### 4.6 Kedaluwarsa

`rentals:expire` (tiap menit) membatalkan rental yang punya payment `Sewa` **masih `Menunggu Pembayaran`** dengan `expires_at <= now()`. Payment → `Kadaluarsa`, rental → `Dibatalkan`. Payment `Menunggu Konfirmasi` / `Berhasil` dilewati.

Logika sama dipakai webhook `expire` / `cancel` di `PaymentStatusUpdater`.

### 4.7 Denda pengembalian (F008)

- Denda dihitung server: `ceil(menit keterlambatan / 60) × config('rental.late_fee_rate_per_hour') × price_per_day`, dibulatkan integer.
- Modal hanya menampilkan estimasi dan konfirmasi **Ya/Tidak**. Server menghitung ulang saat POST; input `late_fee` dari client diabaikan.
- Payment `Denda` dibuat dengan `method = Tunai`, `status = Berhasil`, `confirmed_by = admin`, tanpa baris `cash`.

---

## 5. Konfigurasi

### `.env.example`

```dotenv
MIDTRANS_IS_PRODUCTION=false
MIDTRANS_SERVER_KEY=
MIDTRANS_CLIENT_KEY=
MIDTRANS_SIGNATURE_SECRET_KEY=

RENTAL_LATE_FEE_RATE_PER_HOUR=0.06
```

### `config/midtrans.php`

```php
<?php

declare(strict_types=1);

$isProduction = (bool) env('MIDTRANS_IS_PRODUCTION', false);

return [
    'is_production' => $isProduction,
    'server_key' => env('MIDTRANS_SERVER_KEY'),
    'client_key' => env('MIDTRANS_CLIENT_KEY'),
    'signature_secret_key' => env('MIDTRANS_SIGNATURE_SECRET_KEY'),
    'base_url' => $isProduction
        ? 'https://api.midtrans.com'
        : 'https://api.sandbox.midtrans.com',
];
```

Batas waktu dan tarif denda ada di `config/rental.php`, bukan di sini.

Checklist developer: daftarkan Notification URL `https://domain.com/webhooks/qris` di Dashboard Midtrans, isi `MIDTRANS_SIGNATURE_SECRET_KEY` dari **Settings → Access Keys**, gunakan Sandbox saat development, dan aktifkan Core API untuk Production. Bila Signature Secret Key di-regenerate, nota webhook lama masih bisa memakai key lama; tambahkan dukungan key lama hanya bila memang diperlukan saat rotasi.

---

## 6. Struktur file

```text
app/
├── Services/
│   ├── Midtrans/MidtransClient.php          # Charge, Status, Cancel
│   └── Payment/
│       ├── PaymentStatusUpdater.php         # webhook idempoten
│       └── LateFeeCalculator.php            # denda otomatis
├── Http/Controllers/Webhooks/QrisWebhookController.php
├── Console/Commands/RentalsTickCommand.php
├── Console/Commands/PurgeInactiveCarsCommand.php
config/midtrans.php
routes/web.php
tests/Feature/
```

Tidak ada service `VerifyCashCode`, tidak ada route `/admin/payments/cash/verify`.

---

## 7. Webhook

Route: `POST /webhooks/qris`, di luar CSRF. Laravel 13 memakai:

```php
$middleware->preventRequestForgery(except: ['webhooks/qris']);
```

Aturan:

1. Validasi payload: `order_id`, `status_code`, `gross_amount`, `signature_key`.
2. Signature: `hash('sha512', $order_id.$status_code.$gross_amount.config('midtrans.signature_secret_key'))`, bandingkan dengan `hash_equals()`. Salah → `403`. **Jangan** memakai `midtrans.server_key` untuk perhitungan ini.
3. Cari `qris` berdasarkan `qr_ref = order_id`; tidak ada → `404`.
4. Panggil `MidtransClient::status($orderId)` dan pakai `transaction_status` dari Get Status sebagai sumber kebenaran. Get Status wajib ber-`status_code = 200`, `201` (`201` dipakai Midtrans untuk transaksi `pending`), atau `407` (`407` dipakai Midtrans untuk transaksi `expire`) dan memiliki `transaction_status`; selain itu → `502`. Jangan pernah memakai `transaction_status` dari payload notifikasi sebagai fallback.
5. Teruskan ke `PaymentStatusUpdater` (idempoten berdasarkan `qr_ref`).
6. Simpan respons terakhir di `qris.raw_payload`. Tidak ada kolom Midtrans lain.
7. Balas `200` secepatnya. Jangan mengembalikan data sensitif.

Pemetaan:

| `transaction_status` | Tindakan |
|---|---|
| `settlement` | `Menunggu Konfirmasi` + rental `Menunggu Verifikasi`; `paid_at` tetap kosong |
| `pending` | Tidak berubah |
| `deny`, `failure` | Payment `Gagal` |
| `expire`, `cancel` | Payment `Kadaluarsa`, rental `Dibatalkan` |
| `refund`, `partial_refund`, lainnya | Log saja, jangan ubah status |

---

## 8. Keamanan

- Policy/middleware: customer hanya rental miliknya; aksi admin hanya `role = admin`.
- Webhook dilindungi signature, bukan CSRF.
- Jangan log NIK, foto, atau payload penuh yang mengandung data pribadi. `raw_payload` berisi respons gateway, bukan data diri.
- HTTPS wajib di Production.
- Batasi percobaan pembuatan pembayaran bila diperlukan; jangan menghapus validasi server.

---

## 9. Pengujian wajib

- QRIS charge sukses menyimpan `qr_ref` dan URL QR; `expires_at = +10 menit`; `rental.expires_at` ikut disamakan.
- Charge gagal tidak meninggalkan payment yatim.
- Webhook signature salah, termasuk bila ditandatangani dengan Server Key → `403`.
- Webhook dengan Get Status `pending`/`status_code = 201` → `200`, status payment dan rental tidak berubah, `raw_payload` tersimpan.
- Webhook dengan Get Status `404`/tanpa `transaction_status` → `502`, status payment dan rental tidak berubah.
- Webhook `settlement` → `Menunggu Konfirmasi` + `Menunggu Verifikasi`, tanpa `paid_at`/booking/invoice; endpoint status C-15 melaporkan `paid = true`, halaman menampilkan sukses dan redirect ke C-04, endpoint menolak rental milik customer lain.
- Webhook duplikat tidak menulis `booking_history` ganda.
- Webhook `deny` → `Gagal`; `expire` (Get Status `status_code = 407`) → `Kadaluarsa` + `Dibatalkan`, `cancelled_at` terisi.
- Tunai dibuat tanpa Midtrans; admin konfirmasi langsung dari `Menunggu Pembayaran`.
- QRIS `Menunggu Pembayaran` tidak bisa dikonfirmasi admin.
- Denda dihitung server, input `late_fee` client diabaikan, konfirmasi wajib.
- `rentals:expire`, `rentals:tick`, dan `mobils:purge-inactive` sesuai `usecase_gorental.md`.

---

## 10. Larangan keras

1. Server Key atau Signature Secret Key di frontend/repo/log.
2. Mengubah status rental tanpa `transitionTo()`.
3. Webhook tanpa signature atau tanpa Get Status.
4. Terbitkan booking/invoice sebelum konfirmasi admin.
5. Terima `amount` atau `late_fee` dari input user.
6. Memakai ulang `order_id` atau QR simulasi.
7. Menambah kolom Midtrans di luar `database_gorental.md`.
8. Membuat input/route verifikasi kode tunai.
9. Membuat status `Menunggu Verifikasi` untuk Tunai.
10. Mengarah ke Production tanpa `MIDTRANS_IS_PRODUCTION=true` dan Core API aktif.
