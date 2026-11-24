# AI_RULES: Modul Pembayaran GoRental (Tunai + QRIS Midtrans)

> Aturan **wajib** bagi AI/asisten kode (Claude Code, Cursor, Copilot, dll.) saat membangun modul pembayaran GoRental.
> Revisi ini menyelaraskan aturan dengan `usecase_gorental.md`.

## 0. Urutan Acuan (jika ada konflik)

1. `database_gorental.md`: nama tabel, kolom, dan nilai enum.
2. `usecase_gorental.md`: perilaku bisnis, status, dan transisi.
3. Dokumentasi Midtrans terbaru: parameter dan perilaku API (https://docs.midtrans.com/docs/coreapi-e-money-integration). Jika berbeda dengan file ini, dokumentasi Midtrans menang. Beri tahu developer.
4. File ini: aturan teknis dan keamanan.

Tanda **[ASUMSI]** berarti nama kolom/nilai belum dipastikan dari `database_gorental.md`. Cocokkan dulu sebelum menulis kode. **Jangan menambah tabel/kolom baru tanpa izin developer.**

---

## 1. Konteks Proyek

| Item | Nilai |
|---|---|
| Bisnis | Rental mobil (GoRental) |
| Framework | Laravel 13, PHP 8.4 |
| Aktor | `Customer` (`account.role = user`) dan `Admin` (`account.role = admin`). **Tidak ada role kasir/cashier.** |
| Metode bayar | `QRIS` (Midtrans Core API) dan `Tunai` (kode `TNI-XXXX-XXXX`, dikonfirmasi admin) |
| Entitas utama | `account`, `rental`, `payment` (+ detail `qris` / `cash`), `booking_history` |
| Mata uang | IDR, **bilangan bulat** |
| Zona waktu | `Asia/Jakarta` (Midtrans memakai GMT+7) |

Midtrans yang dipakai hanya **Core API**, bukan Snap. Untuk QRIS, `payment_type` yang dikirim ke Charge API adalah **`gopay`**. Pelanggan boleh membayar dengan aplikasi apa pun yang mendukung QRIS.

> `usecase_gorental.md` menyebut penyedia gateway belum ditentukan. Midtrans dipakai di modul ini, jadi **isolasi semua kode Midtrans di `Services/Midtrans/`** dan jangan biarkan Controller atau Model bergantung langsung padanya. Untuk demo boleh membuat simulasi yang memanggil `PaymentStatusUpdater` yang sama.

---

## 2. Aturan Umum untuk AI (WAJIB)

1. **Jangan pernah** menaruh `MIDTRANS_SERVER_KEY` di frontend, Blade, JavaScript, log, repo Git, atau response API.
2. **Jangan** memanggil `env()` di luar `config/`. Gunakan `config('midtrans.server_key')`.
3. Semua panggilan Charge/Status/Cancel ke Midtrans **hanya dari backend**.
4. **Status payment hanya boleh berubah lewat:**
   - (a) webhook Midtrans yang tervalidasi,
   - (b) Get Status dari backend,
   - (c) aksi admin: memasukkan kode tunai, Konfirmasi Pembayaran, Batalkan Pesanan,
   - (d) scheduler `rentals:expire`.

   **Jangan percaya redirect/callback frontend.**
5. **Status rental hanya boleh berubah lewat `Rental::transitionTo()`**, yang juga menulis `booking_history`. Dilarang `$rental->update(['status' => ...])` langsung. Transisi di luar tabel use case (bagian 2.1) harus ditolak.
6. Gunakan **Sandbox** selama pengembangan. Jangan mengarah ke Production tanpa flag `MIDTRANS_IS_PRODUCTION`.
7. Setiap perubahan status **idempoten** dan dibungkus **`DB::transaction` + `lockForUpdate()`**. Urutan kunci selalu **rental dulu, lalu payment** (mencegah deadlock).
8. Gunakan `declare(strict_types=1);`, `final class`, enum PHP 8.4 backed enum, dan type hint penuh.
9. Jangan menambah package baru tanpa izin. Gunakan **Laravel HTTP Client** (`Http::`).
10. Tulis test untuk setiap alur. Jangan request nyata ke Midtrans di test, gunakan `Http::fake()`.
11. **Jangan menambah perilaku yang tidak tertulis di use case.** Jika tidak jelas, tanya developer. Lihat bagian 17.
12. Pembatalan oleh customer dan refund **di luar lingkup**. Jangan dibuat.

---

## 3. Status (mengikuti use case)

### 3.1 Payment (`payment.status`) **[ASUMSI nilai persis]**

```
Menunggu Pembayaran → Menunggu Konfirmasi → Berhasil
        ├─→ Gagal        (QRIS ditolak/gagal)
        └─→ Kadaluarsa   (lewat expires_at, atau rental dibatalkan)
```
Ejaan baku: **Kadaluarsa**. Tidak ada status `cancelled`, `refunded`, atau `paid`.

Transisi payment yang diizinkan:

| Dari | Ke | Pemicu |
|---|---|---|
| `Menunggu Pembayaran` | `Menunggu Konfirmasi` | QRIS: webhook `settlement`. Tunai: admin memasukkan kode valid |
| `Menunggu Pembayaran` | `Gagal` | QRIS: webhook `deny`/`failure` |
| `Menunggu Pembayaran` | `Kadaluarsa` | Scheduler, webhook `expire`/`cancel`, atau Batalkan Pesanan |
| `Menunggu Konfirmasi` | `Berhasil` | Admin menekan Konfirmasi Pembayaran |
| `Menunggu Konfirmasi` | `Kadaluarsa` | Hanya lewat Batalkan Pesanan oleh admin (**tidak** otomatis) |

`Berhasil`, `Gagal`, dan `Kadaluarsa` adalah status akhir. **Jangan pernah diturunkan** oleh notifikasi yang terlambat.

### 3.2 Rental yang disentuh modul ini

| Dari | Ke | Pemicu |
|---|---|---|
| `Menunggu Pembayaran` | `Menunggu Verifikasi` | Payment menjadi `Menunggu Konfirmasi` |
| `Menunggu Verifikasi` | `Pembayaran Berhasil` | Admin Konfirmasi Pembayaran |
| `Menunggu Pembayaran` / `Menunggu Verifikasi` | `Dibatalkan` | Scheduler (payment `Sewa` masih `Menunggu Pembayaran` dan lewat `expires_at`) |
| `Menunggu Pembayaran` / `Menunggu Verifikasi` / `Pembayaran Berhasil` | `Dibatalkan` | Admin Batalkan Pesanan (alasan wajib) |

Status `Berjalan`, `Terlambat`, dan `Selesai` bukan urusan modul ini (scheduler `rentals:tick` dan F008).

---

## 4. Alur Bisnis

### 4.1 Memilih metode (F005, layar C-14)
1. Rental berstatus `Menunggu Pembayaran` dan `expires_at` belum lewat. Jika tidak, **tolak**.
2. Customer memilih `QRIS` atau `Tunai`.
3. `amount` = `rental.total_rental`, **dihitung server**, bukan dari input. `late_fee` tidak masuk.
4. Satu rental hanya boleh punya **satu payment `Sewa` aktif** (`Menunggu Pembayaran` atau `Menunggu Konfirmasi`) pada satu waktu.
5. Dalam transaksi yang sama dengan pembuatan payment, **`rental.expires_at` disamakan dengan `payment.expires_at`**. Ini penting karena aturan ketersediaan tanggal dan auto-cancel memakai batas waktu yang sama.

### 4.2 Tunai
1. Buat `payment` (`type=Sewa`, `method=Tunai`, `Menunggu Pembayaran`, `expires_at = now + 3 jam`) dan baris `cash` dengan `payment_code` format `TNI-XXXX-XXXX` (unik, retry bila bentrok).
2. **Tidak ada panggilan ke Midtrans.**
3. Tampilkan kode, total, batas 3 jam, instruksi, dan lokasi garasi (C-16).
4. Customer datang ke garasi dan menunjukkan kode.
5. Admin memasukkan kode di A-08 (tab Tunai):
   - Kode tidak ditemukan → pesan **"Kode pembayaran tidak ditemukan."**, input ulang.
   - Payment sudah `Kadaluarsa` atau `expires_at` lewat → **tolak**.
   - Kode valid → payment `Menunggu Konfirmasi`, rental `Menunggu Verifikasi` (via `transitionTo`), tampilkan detail pesanan.
6. Admin memeriksa KTP/selfie lalu menekan Konfirmasi Pembayaran (bagian 4.4).
7. **Hanya role `admin`.**

### 4.3 QRIS
1. Buat `order_id` unik (bagian 7), simpan di `qris.qr_ref`.
2. `POST /v2/charge` dengan `payment_type: "gopay"` dan **masa berlaku 10 menit** (`payment.expires_at = now + 10 menit`).
3. Dari response, ambil `actions[name=generate-qr-code].url` dan simpan. Tampilkan sebagai `<img src="...">` bersama nominal, **hitung mundur 10 menit**, status, dan instruksi (C-15). Deeplink (`deeplink-redirect`) opsional.
4. Frontend **polling** ke endpoint internal (mis. tiap 5 detik) yang membaca status dari **database kita**, bukan dari Midtrans.
5. Midtrans mengirim webhook. Setelah tervalidasi, payment menjadi `Menunggu Konfirmasi` dan rental `Menunggu Verifikasi` (bagian 9).
6. **Webhook sukses BUKAN akhir.** Admin tetap harus memeriksa dan menekan Konfirmasi Pembayaran.
7. QRIS gagal → payment `Gagal`, modal S-04 (Tutup / Bayar Kembali). Customer boleh membuat payment baru (dengan `order_id` baru) selama rental belum `Dibatalkan`.

### 4.4 Konfirmasi Pembayaran oleh Admin (F006, berlaku untuk Tunai dan QRIS)
Hanya bila payment `Menunggu Konfirmasi`. QRIS yang belum diterima (`Menunggu Pembayaran`) **tidak bisa** dikonfirmasi.

Dalam **satu `DB::transaction`** (kunci rental lalu payment):
- payment → `Berhasil`; isi `paid_at`, `confirmed_by`, `confirmed_at`.
- rental → `Pembayaran Berhasil` via `transitionTo()`; isi `booking_code` (`BK-` + 6 alfanumerik huruf besar, unik), `invoice_number` (`INV-{tahun}-{urut}`), `invoice_issued_at`.
- Tulis `booking_history`.
- Kode booking dan invoice **hanya terlihat customer setelah langkah ini**.

Jika data tidak sesuai, admin **tidak** mengonfirmasi. Status tetap `Menunggu Konfirmasi` / `Menunggu Verifikasi`.

> `paid_at` diisi saat **konfirmasi admin**, bukan saat webhook.

### 4.5 Batalkan Pesanan oleh Admin
- Alasan wajib → `rental.cancel_reason`, `cancelled_at`.
- Rental → `Dibatalkan` (via `transitionTo`). Payment yang belum `Berhasil` → `Kadaluarsa`.
- Bila payment QRIS masih `Menunggu Pembayaran`, panggil `MidtransClient::cancel($orderId)` agar QR tidak bisa dibayar lagi. Kegagalan pemanggilan ini hanya di-log, jangan menggagalkan pembatalan.
- **Tidak ada refund.**

### 4.6 Kedaluwarsa (scheduler `rentals:expire`, tiap menit)
- Target: rental `Menunggu Pembayaran` / `Menunggu Verifikasi` yang punya payment `Sewa` **masih `Menunggu Pembayaran`** dengan `payment.expires_at <= now()`.
- Payment → `Kadaluarsa`, rental → `Dibatalkan`.
- Payment `Menunggu Konfirmasi` / `Berhasil` **dilewati**, meski `expires_at` terlewat.
- Berlaku untuk QRIS (10 menit) dan Tunai (3 jam). Kode tunai tidak bisa dipakai lagi setelah `Kadaluarsa`.
- Logika kedaluwarsa dipakai bersama oleh scheduler dan webhook `expire` (satu method di `PaymentStatusUpdater`).

---

## 5. Setup Midtrans (Checklist Developer)

- [ ] Ambil **Server Key** dan **Client Key** Sandbox (Dashboard > Settings > Access Keys).
- [ ] Atur **Payment Notification URL** di Dashboard (Settings > Configuration): `https://domainanda.com/webhooks/qris`. URL harus publik (lokal: ngrok/cloudflared).
- [ ] Production: **ajukan aktivasi Core API** dan pastikan GoPay/QRIS aktif.
- [ ] Server Key Production hanya di environment server production.

---

## 6. Konfigurasi

### `.env`
```dotenv
MIDTRANS_IS_PRODUCTION=false
MIDTRANS_SERVER_KEY=SB-Mid-server-xxxxxxxx
MIDTRANS_CLIENT_KEY=SB-Mid-client-xxxxxxxx
APP_TIMEZONE=Asia/Jakarta
```

### `config/midtrans.php`
```php
<?php

declare(strict_types=1);

return [
    'is_production' => (bool) env('MIDTRANS_IS_PRODUCTION', false),
    'server_key'    => env('MIDTRANS_SERVER_KEY'),
    'client_key'    => env('MIDTRANS_CLIENT_KEY'),

    'base_url' => env('MIDTRANS_IS_PRODUCTION', false)
        ? 'https://api.midtrans.com'
        : 'https://api.sandbox.midtrans.com',

    // Sesuai use case 3.4
    'qris_expiry_minutes' => 10,
    'cash_expiry_hours'   => 3,
];
```
Nilai `rental.contact_phone` dan `driver_fee_per_day` ada di `config/rental.php`, bukan di sini.

---

## 7. Struktur File dan Aturan `order_id`

```
app/
├── Enums/        PaymentMethod, PaymentStatus, PaymentType, RentalStatus   (nilai mengikuti database_gorental.md)
├── Models/       Rental, Payment, Qris, Cash   (relasi payment 1-1 qris|cash)
├── Services/
│   ├── Midtrans/MidtransClient.php         # wrapper HTTP ke Midtrans
│   └── Payment/
│       ├── PaymentStatusUpdater.php        # transisi idempoten (webhook, scheduler)
│       ├── CreatePayment.php               # buat payment Sewa (qris/tunai)
│       ├── VerifyCashCode.php              # admin memasukkan kode tunai
│       ├── ConfirmPayment.php              # Konfirmasi Pembayaran oleh admin
│       └── CancelRental.php                # Batalkan Pesanan oleh admin (bila belum ada di modul rental)
├── Http/Controllers/
│   ├── PaymentController.php               # buat pembayaran, polling status
│   ├── Admin/PaymentVerificationController.php
│   └── Webhooks/QrisWebhookController.php
├── Http/Requests/CreatePaymentRequest.php
├── Console/Commands/ExpireRentals.php      # rentals:expire (jika belum ada)
config/midtrans.php
routes/web.php
tests/Feature/Payment/
```
Migration: **ikuti `database_gorental.md`**. Jangan membuat tabel `payments` tunggal bergaya lama.

**Kolom khusus Midtrans pada tabel `qris` [ASUMSI, cocokkan dulu]:** `qr_ref` (= `order_id`, unik), `qr_url`, `deeplink_url`, `midtrans_transaction_id`, `midtrans_payment_type`, `midtrans_response` (json). Jika kolom ini belum ada di dokumen database, **tanya developer**.

**Aturan `order_id`:**
- Harus unik di Midtrans, maksimal 50 karakter.
- Format: `{trx_code}-{unix_timestamp}`, mis. `TRX-000123-1767225600`.
- **Jangan memakai `booking_code`.** Kode itu baru dibuat saat admin konfirmasi, jadi belum ada saat charge.
- Setiap percobaan bayar ulang **wajib** membuat `order_id` baru.

---

## 8. Integrasi Midtrans

### 8.1 `MidtransClient`
```php
<?php

declare(strict_types=1);

namespace App\Services\Midtrans;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

final class MidtransClient
{
    private function http(): PendingRequest
    {
        // Basic Auth: username = Server Key, password kosong
        return Http::baseUrl(config('midtrans.base_url'))
            ->withBasicAuth((string) config('midtrans.server_key'), '')
            ->acceptJson()
            ->asJson()
            ->timeout(15);
    }

    /** @return array<string,mixed> */
    public function chargeQris(string $orderId, int $grossAmount, int $expiryMinutes, array $extra = []): array
    {
        $payload = array_merge([
            'payment_type' => 'gopay',
            'transaction_details' => [
                'order_id'     => $orderId,
                'gross_amount' => $grossAmount,
            ],
            'gopay' => ['enable_callback' => false],
            // Verifikasi format terbaru di dokumentasi Midtrans sebelum dipakai.
            'custom_expiry' => [
                'order_time'      => now('Asia/Jakarta')->format('Y-m-d H:i:s O'),
                'expiry_duration' => $expiryMinutes,
                'unit'            => 'minute',
            ],
        ], $extra);

        $response = $this->http()->post('/v2/charge', $payload);

        if ($response->failed() || ! in_array($response->json('status_code'), ['200', '201'], true)) {
            // Log tanpa Server Key, jangan bocorkan detail ke user.
            logger()->error('Midtrans charge gagal', [
                'order_id' => $orderId,
                'status'   => $response->status(),
                'body'     => $response->json(),
            ]);
            throw new RuntimeException('Gagal membuat pembayaran QRIS.');
        }

        return $response->json();
    }

    /** @return array<string,mixed> */
    public function status(string $orderId): array
    {
        return $this->http()->get("/v2/{$orderId}/status")->throw()->json();
    }

    /** @return array<string,mixed> */
    public function cancel(string $orderId): array
    {
        return $this->http()->post("/v2/{$orderId}/cancel")->throw()->json();
    }
}
```

### 8.2 `CreatePayment` untuk QRIS (alur wajib)
1. `DB::transaction`: `lockForUpdate()` rental, lalu validasi: status `Menunggu Pembayaran`, `expires_at` belum lewat, tidak ada payment `Sewa` aktif.
2. Hitung `amount = rental.total_rental`, `expires_at = now + qris_expiry_minutes`.
3. Panggil `chargeQris()` **di dalam transaksi** (kunci rental mencegah klik ganda membuat dua charge). Jika gagal, exception membatalkan transaksi sehingga **tidak ada payment yatim**.
4. Buat `payment` + `qris` (`qr_ref`, `qr_url`, `deeplink_url`, `midtrans_response`).
5. Set `rental.expires_at = payment.expires_at`.

### 8.3 Parameter tambahan yang dianjurkan pada charge
```php
'customer_details' => ['first_name' => $name, 'phone' => $phone],   // dari data diri rental/account
'item_details' => [
    ['id' => 'RENTAL-'.$rental->trx_code, 'price' => $subtotal,  'quantity' => 1, 'name' => "Sewa {$carName} ({$duration} hari)"],
    ['id' => 'DRIVER',                    'price' => $driverFee, 'quantity' => 1, 'name' => 'Biaya sopir'],  // hanya bila dengan sopir
],
```
> Total `item_details` **harus sama persis** dengan `gross_amount` (= `total_rental`). Jangan memasukkan `late_fee`.

### 8.4 Mengambil URL QR
```php
$qrUrl    = collect($response['actions'] ?? [])->firstWhere('name', 'generate-qr-code')['url'] ?? null;
$deeplink = collect($response['actions'] ?? [])->firstWhere('name', 'deeplink-redirect')['url'] ?? null;
```

---

## 9. Webhook

**Route:** `POST /webhooks/qris`, **di luar CSRF** (mis. `validateCsrfTokens(except: ['webhooks/qris'])` di `bootstrap/app.php`). Daftarkan URL ini sebagai Notification URL di Midtrans.

**Aturan:**
1. **Validasi signature:** `hash('sha512', $order_id . $status_code . $gross_amount . $serverKey)` harus sama dengan `signature_key`. Gunakan `hash_equals()`. Salah → `403`.
2. Cari `qris` berdasarkan `qr_ref = order_id`. Tidak ada → `404`.
3. **Verifikasi ganda:** panggil `MidtransClient::status($orderId)` dan pakai `transaction_status` dari hasil itu.
4. Teruskan ke `PaymentStatusUpdater` (idempoten).
5. Simpan `payment_type` dari webhook. Jika pelanggan bayar dari aplikasi lain, Midtrans mengirim `payment_type: "qris"` (bukan `gopay`). **Jangan ditolak.**
6. Balas **HTTP 200** secepatnya. Pekerjaan berat lewat queue.
7. Jangan pernah menurunkan status akhir.

**Pemetaan status Midtrans → tindakan:**

| `transaction_status` | Tindakan |
|---|---|
| `settlement` | Payment `Menunggu Pembayaran` → `Menunggu Konfirmasi`; rental → `Menunggu Verifikasi`. **Tidak** mengisi `paid_at`. |
| `pending` | Tidak berbuat apa-apa. |
| `deny`, `failure` | Payment → `Gagal`. Rental tetap `Menunggu Pembayaran`. |
| `expire`, `cancel` | Payment → `Kadaluarsa`; rental → `Dibatalkan` (logika sama dengan scheduler). |
| `refund`, `partial_refund` | **Di luar lingkup.** Catat di log, jangan ubah status. |
| lainnya | Catat di log, jangan ubah status. |

### 9.1 `PaymentStatusUpdater` (inti idempoten)
```php
<?php

declare(strict_types=1);

namespace App\Services\Payment;

use App\Enums\PaymentStatus;
use App\Enums\RentalStatus;
use App\Models\Payment;
use App\Models\Qris;
use App\Models\Rental;
use Illuminate\Support\Facades\DB;

final class PaymentStatusUpdater
{
    /** @param array<string,mixed> $payload hasil Get Status Midtrans */
    public function applyMidtrans(string $orderId, array $payload): Payment
    {
        $qris = Qris::where('qr_ref', $orderId)->firstOrFail();

        return DB::transaction(function () use ($qris, $payload) {
            // Urutan kunci: rental dulu, lalu payment.
            $payment = Payment::findOrFail($qris->payment_id);
            $rental  = Rental::whereKey($payment->rental_id)->lockForUpdate()->firstOrFail();
            $payment = Payment::whereKey($payment->id)->lockForUpdate()->firstOrFail();

            $status = (string) ($payload['transaction_status'] ?? '');

            // Idempoten: hanya payment yang masih menunggu yang boleh diproses.
            if ($payment->status !== PaymentStatus::MenungguPembayaran) {
                if ($status === 'settlement'
                    && in_array($payment->status, [PaymentStatus::Kadaluarsa, PaymentStatus::Gagal], true)) {
                    // Uang masuk setelah payment ditutup. Perlu penanganan manual (lihat bagian 17).
                    logger()->critical('Settlement diterima untuk payment yang sudah ditutup', [
                        'payment_id' => $payment->id,
                        'order_id'   => $qris->qr_ref,
                    ]);
                }
                return $payment;
            }

            match ($status) {
                'settlement'       => $this->markWaitingConfirmation($payment, $rental),
                'deny', 'failure'  => $payment->update(['status' => PaymentStatus::Gagal]),
                'expire', 'cancel' => $this->expire($payment, $rental),
                default            => null, // pending, refund, dan lainnya: abaikan
            };

            $qris->update([
                'midtrans_transaction_id' => $qris->midtrans_transaction_id ?? ($payload['transaction_id'] ?? null),
                'midtrans_payment_type'   => $payload['payment_type'] ?? $qris->midtrans_payment_type,
                'midtrans_response'       => $payload ?: $qris->midtrans_response,
            ]);

            return $payment->refresh();
        });
    }

    private function markWaitingConfirmation(Payment $payment, Rental $rental): void
    {
        $payment->update(['status' => PaymentStatus::MenungguKonfirmasi]);
        $rental->transitionTo(RentalStatus::MenungguVerifikasi); // tanda tangan method sesuai implementasi proyek
    }

    /** Dipakai bersama oleh webhook dan scheduler rentals:expire. */
    public function expire(Payment $payment, Rental $rental): void
    {
        $payment->update(['status' => PaymentStatus::Kadaluarsa]);

        if (in_array($rental->status, [RentalStatus::MenungguPembayaran, RentalStatus::MenungguVerifikasi], true)) {
            $rental->transitionTo(RentalStatus::Dibatalkan);
        }
    }
}
```
> Nama enum case di atas memakai **[ASUMSI]**. Sesuaikan dengan `database_gorental.md`.

---

## 10. Pemetaan Status Midtrans (ringkas)

| Midtrans | Payment | Rental | Catatan |
|---|---|---|---|
| `pending` | `Menunggu Pembayaran` | `Menunggu Pembayaran` | Tampilkan QR |
| `settlement` | `Menunggu Konfirmasi` | `Menunggu Verifikasi` | Menunggu admin |
| `deny` / `failure` | `Gagal` | tetap | Boleh bayar ulang (order_id baru) |
| `expire` / `cancel` | `Kadaluarsa` | `Dibatalkan` | Tidak ada bayar ulang, rental sudah batal |
| `refund` | tidak berubah | tidak berubah | Di luar lingkup, log saja |

---

## 11. Endpoint Internal **[ASUMSI nama route]**

| Method | URL | Fungsi | Akses |
|---|---|---|---|
| POST | `/rentals/{rental}/payments` | Buat payment Sewa (QRIS/Tunai) | Pemilik rental |
| GET | `/payments/{payment}/status` | Polling status dari DB | Pemilik rental |
| POST | `/admin/payments/cash/verify` | Admin memasukkan kode tunai | `admin` |
| POST | `/admin/payments/{payment}/confirm` | Konfirmasi Pembayaran | `admin` |
| POST | `/admin/rentals/{rental}/cancel` | Batalkan Pesanan (alasan wajib) | `admin` |
| POST | `/webhooks/qris` | Webhook Midtrans | Publik (+ signature) |

**Tidak ada** endpoint pembatalan oleh customer.

**Validasi `CreatePaymentRequest`:**
```php
'method' => ['required', Rule::enum(PaymentMethod::class)],
```
`amount` **tidak boleh** datang dari input user. `type` selalu `Sewa` di modul ini (payment `Denda` dibuat F008, bukan di sini).

---

## 12. Aturan Keamanan

- Gunakan **Policy**: customer hanya bisa melihat/membayar rental miliknya. Aksi admin hanya untuk `role = admin`.
- Rate limit pembuatan pembayaran dan polling (`throttle:30,1`).
- Jangan log body penuh yang berisi data pribadi. **NIK, foto KTP, dan selfie jangan pernah masuk log atau `midtrans_response`.** **Jangan pernah log Server Key.**
- Cegah pembayaran ganda (aturan 4.1 poin 4). Jika ada payment QRIS `Menunggu Pembayaran` yang belum kedaluwarsa untuk rental yang sama, kembalikan yang itu, jangan buat baru.
- Tolak pembuatan payment bila rental bukan `Menunggu Pembayaran`.
- **HTTPS** wajib di production.
- Whitelist IP Midtrans opsional. Utamakan validasi signature.

---

## 13. Pengujian

Gunakan `Http::fake()`. Test minimal di `tests/Feature/Payment/`:

**Umum**
- [ ] `amount` selalu dari `total_rental`, input user diabaikan.
- [ ] Rental `expires_at` disamakan dengan `payment.expires_at` saat payment dibuat.
- [ ] Hanya satu payment `Sewa` aktif per rental.
- [ ] User lain tidak bisa melihat atau membayar rental orang lain.
- [ ] Semua perubahan status rental lewat `transitionTo()` dan menulis `booking_history`.

**Tunai**
- [ ] Payment `Menunggu Pembayaran` + kode `TNI-XXXX-XXXX` dibuat tanpa memanggil Midtrans; `expires_at` = +3 jam.
- [ ] Kode tidak ada → "Kode pembayaran tidak ditemukan."
- [ ] Kode kedaluwarsa ditolak.
- [ ] Kode valid → payment `Menunggu Konfirmasi`, rental `Menunggu Verifikasi`.
- [ ] Customer tidak bisa memasukkan kode (hanya admin).

**QRIS**
- [ ] Charge sukses menyimpan `qr_ref`, `qr_url`; `expires_at` = +10 menit.
- [ ] Charge gagal tidak membuat payment yatim.
- [ ] Webhook signature salah → 403.
- [ ] Webhook `settlement` → payment `Menunggu Konfirmasi`, rental `Menunggu Verifikasi`, `paid_at` kosong, **tidak** ada kode booking/invoice.
- [ ] Webhook dikirim 2x tidak menimbulkan efek ganda.
- [ ] Webhook `pending` terlambat tidak menurunkan status.
- [ ] Webhook `payment_type: qris` diterima normal.
- [ ] Webhook `deny` → `Gagal`; customer bisa membuat payment baru dengan `order_id` baru.
- [ ] Webhook `expire` → payment `Kadaluarsa`, rental `Dibatalkan`.
- [ ] Webhook `refund` hanya dicatat di log.

**Konfirmasi dan pembatalan**
- [ ] Konfirmasi hanya untuk payment `Menunggu Konfirmasi`; QRIS `Menunggu Pembayaran` ditolak.
- [ ] Konfirmasi mengisi `paid_at`, `confirmed_by/at`, `booking_code`, `invoice_number`, `invoice_issued_at` dalam satu transaksi.
- [ ] Batalkan Pesanan wajib alasan; payment belum `Berhasil` → `Kadaluarsa`.

**Scheduler `rentals:expire`**
- [ ] Payment `Menunggu Pembayaran` lewat `expires_at` → `Kadaluarsa`, rental `Dibatalkan`.
- [ ] Payment `Menunggu Konfirmasi` / `Berhasil` **dilewati**.

---

## 14. Checklist Go-Live

- [ ] Core API Production sudah **diaktifkan Midtrans**.
- [ ] Server/Client Key **Production**, `MIDTRANS_IS_PRODUCTION=true`.
- [ ] Notification URL Production (`/webhooks/qris`) sudah diatur.
- [ ] `php artisan config:cache`, `.env` tidak ikut repo.
- [ ] Scheduler (`schedule:run` tiap menit) dan queue worker berjalan.
- [ ] Uji 1 transaksi nyata bernilai kecil sampai tahap Konfirmasi Pembayaran admin.
- [ ] Pantau log error Midtrans (terutama `critical`) selama minggu pertama.

---

## 15. Larangan Keras

1. Server Key di frontend atau repo.
2. Menandai payment `Berhasil` otomatis dari webhook. **Itu hanya lewat Konfirmasi Pembayaran admin.**
3. Menerbitkan `booking_code` / `invoice_number` sebelum admin konfirmasi.
4. Mengubah status rental tanpa `Rental::transitionTo()`.
5. Menerima `amount` dari input user.
6. Webhook tanpa validasi signature.
7. Memakai ulang `order_id`.
8. Update status tanpa transaksi DB dan lock.
9. Mengarah ke Production saat fase pengembangan.
10. Membuat role `cashier`, fitur batal oleh customer, atau fitur refund.
11. Menebak parameter Midtrans atau nama kolom tanpa memeriksa dokumentasi / `database_gorental.md`.

---

## 16. Urutan Pengerjaan untuk AI

1. Baca `database_gorental.md`. Sesuaikan enum dan model (`Payment`, `Qris`, `Cash`, relasi ke `Rental`).
2. `config/midtrans.php`, `.env.example`.
3. `MidtransClient` dan `PaymentStatusUpdater` beserta unit test.
4. Alur **Tunai**: buat payment + kode, verifikasi kode admin.
5. **Konfirmasi Pembayaran** admin (berlaku untuk Tunai dan QRIS).
6. Alur **QRIS**: charge, tampilkan QR, polling.
7. Webhook, signature, idempotensi.
8. Batalkan Pesanan dan scheduler `rentals:expire`.
9. Policy, rate limit.
10. Test end-to-end di Sandbox, lalu checklist go-live.

---

## 17. Pertanyaan Terbuka (tanyakan ke developer, jangan menebak)

1. **Settlement setelah payment ditutup.** Jika webhook `settlement` tiba saat payment sudah `Kadaluarsa`/`Gagal` (uang masuk tapi rental sudah batal), use case tidak mengatur. Perilaku default di file ini: status tidak diubah, `logger()->critical`, dan ditangani manual. Opsi tambahan: scheduler memanggil Get Status Midtrans sebelum menandai QRIS `Kadaluarsa`.
2. **Pindah metode.** Bolehkah customer pindah dari Tunai ke QRIS (atau sebaliknya) saat payment pertama masih `Menunggu Pembayaran`? Default: ditolak (satu payment aktif).
3. **Audit log.** Versi lama mewajibkan audit log untuk tunai. Use case hanya punya `booking_history`. Default: hanya `booking_history`.
4. **Kolom Midtrans** pada tabel `qris` (`midtrans_transaction_id`, `midtrans_payment_type`, `midtrans_response`, `deeplink_url`): sudah ada di `database_gorental.md`?
5. **Nilai enum dan nama route** pada bagian 3 dan 11 (`QRIS`/`Tunai`, dll.).
