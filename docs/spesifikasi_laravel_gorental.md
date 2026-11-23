# Spesifikasi Laravel — GoRental (Aplikasi Rental Mobil)

Dokumen ini adalah versi **khusus Laravel** dari `spesifikasi_database_gorental.md`. Struktur tabel, kolom, relasi, enum, dan aturan bisnis **tetap mengacu ke dokumen tersebut** (dan Bab 2.1 laporan RPL113). Dokumen ini menerjemahkannya menjadi migration, model Eloquent, auth, middleware, dan struktur aplikasi Laravel.

Target: **Laravel 11 atau lebih baru**, PHP 8.2+, MySQL/MariaDB.

---

## 0. Instruksi untuk AI

Database lama sudah ada dengan nama tabel/kolom yang berbeda dari rancangan. Kerjakan berurutan:

1. **Audit** struktur database dan kode yang ada, lalu bandingkan dengan dokumen ini.
2. **Pilih strategi migrasi:**
   - **Data masih dummy / boleh dihapus** → hapus migration lama, buat ulang sesuai Bagian 3, lalu `php artisan migrate:fresh --seed`.
   - **Data harus dipertahankan** → jangan `migrate:fresh`. Buat migration baru berisi `Schema::rename()` dan `$table->renameColumn()` (butuh Laravel 11+ atau `doctrine/dbal` di versi lama), lalu tambahkan kolom yang kurang. Tulis migration `down()` yang bisa membalikkan.
3. **Pakai nama persis** dari dokumen ini: nama tabel **tunggal** dan huruf kecil (`account`, `car`, `rental`, ...), PK seperti `car_id`.
4. **Perbarui semua kode** yang memakai nama lama (model, controller, view, route, seeder, factory).
5. Jangan menulis SQL mentah untuk membuat tabel. Gunakan **migration Laravel**.
6. Di akhir, keluarkan: (a) tabel pemetaan nama lama → baru, (b) daftar file yang dibuat/diubah, (c) asumsi yang diambil.

### Konvensi penting (beda dari default Laravel)

| Default Laravel | Proyek ini | Konsekuensi |
|---|---|---|
| Nama tabel jamak (`cars`) | Tunggal (`car`) | Wajib `protected $table` di setiap model |
| PK bernama `id` | `car_id`, `rental_id`, dst. | Wajib `protected $primaryKey` |
| Tabel `users` | Tabel `account` | Ubah `config/auth.php`, hapus migration `users` bawaan |
| PK bigint unsigned | `INT(11)` (unsigned integer) | Pakai `increments()` dan `unsignedInteger()` untuk FK, **bukan** `id()` / `foreignId()` |
| `created_at`/`updated_at` di semua tabel | Hanya di tabel tertentu | Set `public $timestamps = false` bila tidak dipakai |

---

## 1. Struktur Folder Aplikasi

```
app/
├── Enums/
│   ├── AccountRole.php
│   ├── CarStatus.php
│   ├── RentalStatus.php
│   ├── PaymentStatus.php
│   ├── PaymentMethod.php
│   └── HandoverMethod.php
├── Http/
│   ├── Controllers/
│   │   ├── Auth/            (CustomerAuthController, AdminAuthController)
│   │   ├── Customer/        (CatalogController, BookingController, PaymentController, HistoryController, ProfileController)
│   │   └── Admin/           (DashboardController, CarController, OrderController, HistoryController)
│   ├── Middleware/EnsureRole.php
│   └── Requests/            (RegisterRequest, LoginRequest, CarRequest, BookingRequest, ...)
├── Models/                  (Account, Brand, CarType, Car, CarPhoto, Rental, AddOn, Payment, Qris, Cash, BookingHistory)
├── Services/
│   ├── AvailabilityService.php
│   ├── RentalService.php
│   └── PaymentService.php
└── Console/Commands/ExpirePayments.php
database/
├── migrations/
└── seeders/ (AdminSeeder, BrandSeeder, CarTypeSeeder, DatabaseSeeder)
```

---

## 2. Enum PHP (`app/Enums`)

Gunakan *backed enum* agar nilai konsisten dengan kolom enum di database. Nilai harus sama dengan dokumen database.

```php
<?php
namespace App\Enums;

enum AccountRole: string { case Admin = 'admin'; case Customer = 'customer'; }

enum CarStatus: string {
    case Tersedia = 'tersedia'; case Disewa = 'disewa';
    case Perbaikan = 'perbaikan'; case Nonaktif = 'nonaktif';
}

enum HandoverMethod: string { case Diantar = 'diantar'; case AmbilDiGarasi = 'ambil_di_garasi'; }

enum PaymentMethod: string { case Qris = 'qris'; case Cash = 'cash'; }

enum PaymentStatus: string {
    case MenungguPembayaran = 'menunggu_pembayaran';
    case MenungguKonfirmasi = 'menunggu_konfirmasi';
    case Berhasil = 'berhasil'; case Gagal = 'gagal'; case Kadaluarsa = 'kadaluarsa';
}

enum RentalStatus: string {
    case MenungguPembayaran = 'menunggu_pembayaran';
    case MenungguVerifikasi = 'menunggu_verifikasi';
    case PembayaranBerhasil = 'pembayaran_berhasil';
    case Berjalan = 'berjalan'; case Terlambat = 'terlambat';
    case Selesai = 'selesai'; case Dibatalkan = 'dibatalkan'; case Kadaluarsa = 'kadaluarsa';

    /** Status yang masih memblokir mobil (dipakai cek ketersediaan). */
    public static function aktif(): array {
        return [self::MenungguPembayaran, self::MenungguVerifikasi,
                self::PembayaranBerhasil, self::Berjalan, self::Terlambat];
    }
}
```

> Tiap enum ditaruh di file sendiri (`AccountRole.php`, dst.), blok di atas digabung hanya untuk ringkas.

---

## 3. Migration

**Hapus** migration bawaan `create_users_table` (atau ubah jadi `account`). Bila memakai `SESSION_DRIVER=database`, pertahankan tabel `sessions`, tetapi pisahkan dari migration users. Cara paling sederhana: set `SESSION_DRIVER=file` di `.env`.

**Urutan pembuatan (wajib, karena FK):**
`account → brand → car_type → car → car_photo → rental → add_on → payment → qris → cash → booking_history`

Penamaan file: `2026_01_01_000001_create_account_table.php`, `..._000002_create_brand_table.php`, dst.

```php
// 1. account
Schema::create('account', function (Blueprint $t) {
    $t->increments('account_id');
    $t->string('name', 100);
    $t->string('password');                       // hash, 255
    $t->enum('role', ['admin', 'customer'])->default('customer');
    $t->string('phone_number', 15)->unique();
    $t->string('email', 100)->unique();
    $t->string('photo_profil', 255)->nullable();
    $t->rememberToken();                          // untuk fitur "ingat saya"
    $t->timestamps();
});

// 2. brand
Schema::create('brand', function (Blueprint $t) {
    $t->increments('brand_id');
    $t->string('brand_name', 50);
});

// 3. car_type
Schema::create('car_type', function (Blueprint $t) {
    $t->increments('type_id');
    $t->string('type_name', 50);
});

// 4. car
Schema::create('car', function (Blueprint $t) {
    $t->increments('car_id');
    $t->string('model', 100);
    $t->string('license_plate', 15)->unique();
    $t->text('specification')->nullable();
    $t->unsignedInteger('daily_price');
    $t->enum('status', ['tersedia','disewa','perbaikan','nonaktif'])->default('tersedia');
    $t->unsignedInteger('brand_id');
    $t->unsignedInteger('type_id');
    $t->timestamps();

    $t->foreign('brand_id')->references('brand_id')->on('brand');
    $t->foreign('type_id')->references('type_id')->on('car_type');
});

// 5. car_photo
Schema::create('car_photo', function (Blueprint $t) {
    $t->increments('photo_id');
    $t->string('photo_file', 255);
    $t->unsignedInteger('car_id');
    $t->foreign('car_id')->references('car_id')->on('car')->cascadeOnDelete();
});

// 6. rental
Schema::create('rental', function (Blueprint $t) {
    $t->increments('rental_id');
    $t->unsignedSmallInteger('duration');
    $t->unsignedInteger('total_rental');
    $t->dateTime('start_date');
    $t->dateTime('end_date');
    $t->boolean('self_driver');                   // 1 = lepas kunci, 0 = dengan sopir
    $t->string('customer_name', 100);
    $t->string('customer_nik', 16);
    $t->string('customer_phone', 15);
    $t->string('customer_ktp_photo', 255);        // path di disk private
    $t->string('customer_selfie_photo', 255);     // path di disk private
    $t->unsignedInteger('account_id');
    $t->unsignedInteger('car_id');
    // tambahan (lihat dokumen database, Bagian 3)
    $t->string('booking_code', 20)->nullable()->unique();
    $t->enum('status', ['menunggu_pembayaran','menunggu_verifikasi','pembayaran_berhasil',
                        'berjalan','terlambat','selesai','dibatalkan','kadaluarsa'])
       ->default('menunggu_pembayaran');
    $t->unsignedInteger('late_fee')->default(0);
    $t->timestamps();

    $t->index(['car_id', 'start_date', 'end_date']);   // mempercepat cek ketersediaan
    $t->foreign('account_id')->references('account_id')->on('account');
    $t->foreign('car_id')->references('car_id')->on('car');
});

// 7. add_on (PK = FK)
Schema::create('add_on', function (Blueprint $t) {
    $t->unsignedInteger('rental_id')->primary();
    $t->boolean('with_driver')->default(false);
    $t->enum('handover_method', ['diantar', 'ambil_di_garasi'])->nullable();
    $t->string('pickup_point', 255)->nullable();
    $t->unsignedInteger('driver_fee')->default(0);
    $t->foreign('rental_id')->references('rental_id')->on('rental')->cascadeOnDelete();
});

// 8. payment
Schema::create('payment', function (Blueprint $t) {
    $t->increments('payment_id');
    $t->dateTime('payment_date')->nullable();
    $t->decimal('amount', 10, 2);
    $t->unsignedInteger('rental_id');
    // tambahan
    $t->enum('method', ['qris', 'cash']);
    $t->enum('status', ['menunggu_pembayaran','menunggu_konfirmasi','berhasil','gagal','kadaluarsa'])
       ->default('menunggu_pembayaran');
    $t->dateTime('expired_at')->nullable();
    $t->unsignedInteger('confirmed_by')->nullable();
    $t->dateTime('confirmed_at')->nullable();
    $t->timestamps();

    $t->foreign('rental_id')->references('rental_id')->on('rental');
    $t->foreign('confirmed_by')->references('account_id')->on('account')->nullOnDelete();
});

// 9. qris (PK = FK)
Schema::create('qris', function (Blueprint $t) {
    $t->unsignedInteger('payment_id')->primary();
    $t->string('qr_ref', 100);
    $t->string('qr_code', 255);
    $t->foreign('payment_id')->references('payment_id')->on('payment')->cascadeOnDelete();
});

// 10. cash (PK = FK)
Schema::create('cash', function (Blueprint $t) {
    $t->unsignedInteger('payment_id')->primary();
    $t->string('payment_code', 50)->unique();     // format TNI-XXXX-XXXX
    $t->foreign('payment_id')->references('payment_id')->on('payment')->cascadeOnDelete();
});

// 11. booking_history
Schema::create('booking_history', function (Blueprint $t) {
    $t->increments('history_id');                 // surrogate key, karena Eloquent tidak mendukung PK komposit
    $t->unsignedSmallInteger('sequence_no');
    $t->unsignedInteger('rental_id');
    $t->enum('status', ['menunggu_pembayaran','menunggu_verifikasi','pembayaran_berhasil',
                        'berjalan','terlambat','selesai','dibatalkan','kadaluarsa']);
    $t->timestamp('changed_at')->useCurrent();

    $t->unique(['rental_id', 'sequence_no']);     // menggantikan PK komposit rancangan
    $t->foreign('rental_id')->references('rental_id')->on('rental')->cascadeOnDelete();
});
```

**Penyimpangan dari rancangan (catat di laporan):**
- `booking_history`: rancangan memakai PK komposit (`rental_id`, `sequence_no`). Di Laravel diganti `history_id` sebagai PK, dengan **unique index** `(rental_id, sequence_no)` sehingga makna datanya sama.
- `password` 255 karakter, dan kolom tambahan di tabel `rental`/`payment` seperti pada dokumen database Bagian 3.
- `account` mendapat `remember_token` dan `timestamps` (kebutuhan auth Laravel).

---

## 4. Model Eloquent

Aturan umum: setiap model menulis `$table`, `$primaryKey`, `$fillable`, dan `casts`.

```php
// app/Models/Account.php
use Illuminate\Foundation\Auth\User as Authenticatable;

class Account extends Authenticatable
{
    protected $table = 'account';
    protected $primaryKey = 'account_id';
    protected $fillable = ['name','password','role','phone_number','email','photo_profil'];
    protected $hidden = ['password','remember_token'];

    protected function casts(): array {
        return ['password' => 'hashed', 'role' => AccountRole::class];
    }

    public function rentals() { return $this->hasMany(Rental::class, 'account_id', 'account_id'); }
    public function isAdmin(): bool { return $this->role === AccountRole::Admin; }
}

// app/Models/Brand.php
class Brand extends Model {
    protected $table = 'brand'; protected $primaryKey = 'brand_id'; public $timestamps = false;
    protected $fillable = ['brand_name'];
    public function cars() { return $this->hasMany(Car::class, 'brand_id', 'brand_id'); }
}

// app/Models/CarType.php
class CarType extends Model {
    protected $table = 'car_type'; protected $primaryKey = 'type_id'; public $timestamps = false;
    protected $fillable = ['type_name'];
    public function cars() { return $this->hasMany(Car::class, 'type_id', 'type_id'); }
}

// app/Models/Car.php
class Car extends Model {
    protected $table = 'car'; protected $primaryKey = 'car_id';
    protected $fillable = ['model','license_plate','specification','daily_price','status','brand_id','type_id'];
    protected function casts(): array { return ['status' => CarStatus::class]; }

    public function brand()   { return $this->belongsTo(Brand::class, 'brand_id', 'brand_id'); }
    public function type()    { return $this->belongsTo(CarType::class, 'type_id', 'type_id'); }
    public function photos()  { return $this->hasMany(CarPhoto::class, 'car_id', 'car_id'); }
    public function rentals() { return $this->hasMany(Rental::class, 'car_id', 'car_id'); }
}

// app/Models/CarPhoto.php
class CarPhoto extends Model {
    protected $table = 'car_photo'; protected $primaryKey = 'photo_id'; public $timestamps = false;
    protected $fillable = ['photo_file','car_id'];
    public function car() { return $this->belongsTo(Car::class, 'car_id', 'car_id'); }
}

// app/Models/Rental.php
class Rental extends Model {
    protected $table = 'rental'; protected $primaryKey = 'rental_id';
    protected $fillable = ['duration','total_rental','start_date','end_date','self_driver',
        'customer_name','customer_nik','customer_phone','customer_ktp_photo','customer_selfie_photo',
        'account_id','car_id','booking_code','status','late_fee'];
    protected function casts(): array {
        return ['start_date'=>'datetime','end_date'=>'datetime','self_driver'=>'boolean',
                'status'=>RentalStatus::class];
    }

    public function account()  { return $this->belongsTo(Account::class, 'account_id', 'account_id'); }
    public function car()      { return $this->belongsTo(Car::class, 'car_id', 'car_id'); }
    public function addOn()    { return $this->hasOne(AddOn::class, 'rental_id', 'rental_id'); }
    public function payments() { return $this->hasMany(Payment::class, 'rental_id', 'rental_id'); }
    public function histories(){ return $this->hasMany(BookingHistory::class, 'rental_id', 'rental_id')->orderBy('sequence_no'); }
}

// app/Models/AddOn.php  (PK = FK, bukan auto increment)
class AddOn extends Model {
    protected $table = 'add_on'; protected $primaryKey = 'rental_id';
    public $incrementing = false; public $timestamps = false;
    protected $fillable = ['rental_id','with_driver','handover_method','pickup_point','driver_fee'];
    protected function casts(): array { return ['with_driver'=>'boolean','handover_method'=>HandoverMethod::class]; }
    public function rental() { return $this->belongsTo(Rental::class, 'rental_id', 'rental_id'); }
}

// app/Models/Payment.php
class Payment extends Model {
    protected $table = 'payment'; protected $primaryKey = 'payment_id';
    protected $fillable = ['payment_date','amount','rental_id','method','status','expired_at','confirmed_by','confirmed_at'];
    protected function casts(): array {
        return ['payment_date'=>'datetime','expired_at'=>'datetime','confirmed_at'=>'datetime',
                'method'=>PaymentMethod::class,'status'=>PaymentStatus::class];
    }
    public function rental() { return $this->belongsTo(Rental::class, 'rental_id', 'rental_id'); }
    public function admin()  { return $this->belongsTo(Account::class, 'confirmed_by', 'account_id'); }
    public function qris()   { return $this->hasOne(Qris::class, 'payment_id', 'payment_id'); }
    public function cash()   { return $this->hasOne(Cash::class, 'payment_id', 'payment_id'); }
}

// app/Models/Qris.php dan Cash.php (PK = FK)
class Qris extends Model {
    protected $table = 'qris'; protected $primaryKey = 'payment_id';
    public $incrementing = false; public $timestamps = false;
    protected $fillable = ['payment_id','qr_ref','qr_code'];
    public function payment() { return $this->belongsTo(Payment::class, 'payment_id', 'payment_id'); }
}
class Cash extends Model {
    protected $table = 'cash'; protected $primaryKey = 'payment_id';
    public $incrementing = false; public $timestamps = false;
    protected $fillable = ['payment_id','payment_code'];
    public function payment() { return $this->belongsTo(Payment::class, 'payment_id', 'payment_id'); }
}

// app/Models/BookingHistory.php
class BookingHistory extends Model {
    protected $table = 'booking_history'; protected $primaryKey = 'history_id';
    public $timestamps = false;
    protected $fillable = ['sequence_no','rental_id','status','changed_at'];
    protected function casts(): array { return ['status'=>RentalStatus::class,'changed_at'=>'datetime']; }
    public function rental() { return $this->belongsTo(Rental::class, 'rental_id', 'rental_id'); }
}
```

---

## 5. Autentikasi dan Role (F001, F009)

**`config/auth.php`:**
```php
'providers' => [
    'users' => ['driver' => 'eloquent', 'model' => App\Models\Account::class],
],
```
(Hapus/abaikan `App\Models\User` bawaan.)

**Aturan login:**
- Customer login dengan `email` **atau** `phone_number` + password. Deteksi: bila input mengandung `@` → kolom `email`, selain itu → `phone_number`.
- Admin login lewat halaman terpisah (`/admin/login`). Field di UI berlabel "Username", tetapi cukup memakai kolom `email` (tidak ada kolom `username` di rancangan).
- Setelah autentikasi, **wajib cek `role`**: akun admin tidak boleh masuk lewat login customer dan sebaliknya. Bila tidak cocok, tampilkan pesan "Email/nomor HP atau password salah."
- Pendaftaran hanya membuat `role = customer`. Akun admin dibuat lewat seeder.
- Password minimal 8 karakter, wajib konfirmasi (`confirmed`), email dan nomor HP `unique:account`.
- Logout: `POST /logout` → `Auth::logout()`, invalidate session, regenerate token, redirect ke halaman login (F009).

**Middleware role (`app/Http/Middleware/EnsureRole.php`):**
```php
public function handle(Request $request, Closure $next, string $role)
{
    $user = $request->user();
    if (!$user || $user->role->value !== $role) abort(403);
    return $next($request);
}
```
Daftarkan alias di `bootstrap/app.php`:
```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->alias(['role' => \App\Http\Middleware\EnsureRole::class]);
})
```

---

## 6. Routing (`routes/web.php`)

```php
// Publik
Route::view('/', 'landing')->name('landing');                          // C-01
Route::middleware('guest')->group(function () {
    Route::get('/login',  [CustomerAuthController::class,'showLogin'])->name('login');    // C-02
    Route::post('/login', [CustomerAuthController::class,'login']);
    Route::get('/daftar', [CustomerAuthController::class,'showRegister']);                // C-03
    Route::post('/daftar',[CustomerAuthController::class,'register']);
    Route::get('/admin/login',  [AdminAuthController::class,'showLogin']);                // A-01
    Route::post('/admin/login', [AdminAuthController::class,'login']);
});
Route::post('/logout', [CustomerAuthController::class,'logout'])->middleware('auth')->name('logout');

// Customer
Route::middleware(['auth','role:customer'])->group(function () {
    Route::get('/dashboard', [DashboardController::class,'index']);                        // C-04
    Route::get('/katalog', [CatalogController::class,'index']);                            // C-05, F002
    Route::get('/katalog/{car}', [CatalogController::class,'show']);                       // C-06
    Route::prefix('booking')->group(function () {                                          // C-07 .. C-13, F004
        Route::get('/{car}/tanggal',  [BookingController::class,'dates']);
        Route::post('/{car}/cek',     [BookingController::class,'checkAvailability']);
        Route::get('/syarat',         [BookingController::class,'terms']);
        Route::get('/layanan',        [BookingController::class,'service']);
        Route::post('/layanan',       [BookingController::class,'storeService']);
        Route::get('/data-diri',      [BookingController::class,'identity']);
        Route::post('/data-diri',     [BookingController::class,'storeIdentity']);
        Route::get('/ringkasan',      [BookingController::class,'summary']);
        Route::post('/konfirmasi',    [BookingController::class,'confirm']);
    });
    Route::get('/pembayaran/{rental}',        [PaymentController::class,'choose']);        // C-14, F005
    Route::post('/pembayaran/{rental}',       [PaymentController::class,'store']);
    Route::get('/pembayaran/{rental}/qris',   [PaymentController::class,'qris']);          // C-15
    Route::get('/pembayaran/{rental}/tunai',  [PaymentController::class,'cash']);          // C-16
    Route::get('/riwayat', [HistoryController::class,'index']);                            // C-17, F007
    Route::get('/riwayat/{rental}', [HistoryController::class,'show']);                    // C-18
    Route::get('/profil', [ProfileController::class,'show']);                              // C-19
    Route::put('/profil', [ProfileController::class,'update']);
});

// Admin
Route::prefix('admin')->middleware(['auth','role:admin'])->group(function () {
    Route::get('/', [Admin\DashboardController::class,'index']);                           // A-02
    Route::resource('mobil', Admin\CarController::class)->parameters(['mobil' => 'car']);  // A-03..A-05, F003
    Route::get('/pesanan', [Admin\OrderController::class,'index']);                        // A-06
    Route::get('/pesanan/{rental}', [Admin\OrderController::class,'show']);                // A-07
    Route::post('/pesanan/{rental}/konfirmasi', [Admin\OrderController::class,'confirmPayment']); // F006
    Route::post('/pesanan/{rental}/selesai',    [Admin\OrderController::class,'complete']);       // F008
    Route::post('/pesanan/{rental}/batal',      [Admin\OrderController::class,'cancel']);
    Route::get('/riwayat', [Admin\HistoryController::class,'index']);                      // A-09, F007
    Route::get('/riwayat/{rental}', [Admin\HistoryController::class,'show']);              // A-10
    Route::get('/dokumen/{rental}/{type}', [Admin\OrderController::class,'document']);     // KTP/selfie (private)
});

// Webhook pembayaran QRIS (jika memakai payment gateway) -> kecualikan dari CSRF
Route::post('/webhook/qris', [PaymentController::class,'webhook']);
```

Gunakan **route model binding dengan PK custom**: karena `$primaryKey` sudah diset di model, `{car}`, `{rental}` otomatis memakai `car_id`/`rental_id`.

**Otorisasi kepemilikan:** customer hanya boleh membuka `rental` miliknya (`$rental->account_id === auth()->id()`), gunakan Policy atau `abort(403)`.

---

## 7. Service Layer (logika bisnis)

Letakkan logika di `app/Services`, bukan di controller. Operasi yang mengubah beberapa tabel **wajib** dibungkus `DB::transaction()`.

### `AvailabilityService`
```php
public function isAvailable(Car $car, Carbon $start, Carbon $end): bool
{
    if ($car->status !== CarStatus::Tersedia) return false;

    return !Rental::where('car_id', $car->car_id)
        ->whereIn('status', array_map(fn($s) => $s->value, RentalStatus::aktif()))
        ->where('start_date', '<', $end)
        ->where('end_date', '>', $start)
        ->exists();
}
```
> Catatan: `car.status = 'disewa'` berarti sedang digunakan **sekarang**. Pencegahan pemesanan ganda untuk tanggal mendatang dilakukan oleh pengecekan tumpang tindih di `rental` (di atas).

### `RentalService`
- `create(Account $account, Car $car, array $data): Rental`
  - Dalam transaksi: kunci baris mobil (`Car::lockForUpdate()`), cek ulang ketersediaan, hitung `duration` (hari), hitung `total_rental = duration × daily_price + driver_fee`, simpan file KTP/selfie ke disk **private**, buat `rental` + `add_on`, lalu panggil `changeStatus(MenungguPembayaran)`.
- `changeStatus(Rental $rental, RentalStatus $status)`
  - Update `rental.status` dan **selalu** tambah baris `booking_history` dengan `sequence_no = (max sequence_no rental ini) + 1`.
  - Dalam transaksi yang sama dengan operasi pemanggil.
- `confirmPayment(Rental $rental, Account $admin)`
  - Hanya jika `payment.status` ∈ {`menunggu_konfirmasi`} untuk QRIS atau saat kode tunai sudah dicek admin.
  - Set `payment.status = berhasil`, isi `confirmed_by`, `confirmed_at`, `payment_date`.
  - Generate `booking_code` unik (`BK-` + 6 karakter alfanumerik kapital, ulangi bila bentrok).
  - `changeStatus(PembayaranBerhasil)`. Invoice berisi kode booking dan nomor HP sopir/admin.
- `complete(Rental $rental, ?int $lateFee)`
  - Bila `now() > end_date` → wajib isi `late_fee` (S-09), simpan ke `rental.late_fee`.
  - `changeStatus(Selesai)`; set `car.status = tersedia`.
- `cancel(Rental $rental)` → `changeStatus(Dibatalkan)` dan bebaskan mobil.

### `PaymentService`
- `createQris(Rental $rental)`: buat `payment` (`method=qris`, `expired_at = now()+10 menit`) + baris `qris` (`qr_ref`, `qr_code`). Bila pakai payment gateway, `qr_code` berisi URL/string QR dari gateway.
- `createCash(Rental $rental)`: buat `payment` (`method=cash`, `expired_at = now()+3 jam`) + baris `cash` dengan `payment_code` format `TNI-XXXX-XXXX` (unik).
- `markQrisPaid(Payment $p)` (dari webhook): `status = menunggu_konfirmasi`, `rental.status = menunggu_verifikasi`, kirim notifikasi ke admin.
- `markQrisFailed(Payment $p)`: `status = gagal`; customer boleh membayar ulang selama transaksi belum kedaluwarsa.
- `findCashByCode(string $code)`: dipakai admin mencari kode tunai (A-08).

### Ketersediaan mobil (`car.status`)
- Ubah ke `disewa` saat rental berstatus `berjalan`; kembali `tersedia` saat `selesai`/`dibatalkan`/`kadaluarsa`.
- **Hapus mobil (F003 alt. 2):** bila ada rental berstatus aktif → set `car.status = nonaktif` saja. Hapus otomatis lewat scheduled job setelah tidak ada lagi rental aktif. Jangan `delete()` langsung karena terlindungi FK.

---

## 8. Kedaluwarsa Otomatis (Scheduler)

Buat command `php artisan make:command ExpirePayments --command=payments:expire`.

Tugasnya: cari `payment` dengan `status = menunggu_pembayaran` dan `expired_at < now()`, lalu dalam transaksi set `payment.status = kadaluarsa` dan `RentalService::changeStatus($rental, Kadaluarsa)`. Kode tunai yang sudah kedaluwarsa tidak boleh dipakai lagi.

Daftarkan di `routes/console.php`:
```php
Schedule::command('payments:expire')->everyMinute();
Schedule::command('cars:purge-inactive')->hourly();   // hapus mobil nonaktif tanpa rental aktif
```
Server harus menjalankan `php artisan schedule:work` (dev) atau cron `* * * * * php artisan schedule:run` (produksi).

---

## 9. Penyimpanan File (Upload)

| Jenis file | Kolom | Disk | Catatan |
|---|---|---|---|
| Foto mobil | `car_photo.photo_file` | `public` | `php artisan storage:link` |
| Foto profil | `account.photo_profil` | `public` | |
| **Foto KTP** | `rental.customer_ktp_photo` | **`local` (private)** | Jangan bisa diakses lewat URL publik |
| **Swafoto** | `rental.customer_selfie_photo` | **`local` (private)** | |

- Simpan **path relatif** di database, bukan URL penuh.
- Validasi upload: `image|mimes:jpg,jpeg,png|max:2048`.
- Simpan dengan nama acak: `$file->store('ktp', 'local')`.
- Admin melihat dokumen lewat route `GET /admin/dokumen/{rental}/{type}` (type: `ktp`/`selfie`) yang di dalamnya memakai `Storage::disk('local')->response($path)`; route ini hanya untuk `role:admin`. Ini memenuhi kebutuhan keamanan (UU PDP) pada laporan.

---

## 10. Validasi (Form Request)

| Request | Aturan inti |
|---|---|
| `RegisterRequest` | `name` required max:100; `email` required email unique:account,email; `phone_number` required regex `^08\d{8,13}$` unique:account,phone_number; `password` required min:8 confirmed |
| `LoginRequest` | `identifier` required; `password` required |
| `CarRequest` | `model`, `brand_id` exists, `type_id` exists, `license_plate` unique (abaikan ID sendiri saat edit), `daily_price` integer min:1, `status` in enum, `specification`, foto image max 2MB (wajib saat tambah) |
| `BookingRequest` | `start_date` date after_or_equal:today; `end_date` date after:start_date; `service` in (`sopir`,`lepas_kunci`); `pickup_point` required_if sopir/diantar; `customer_name` required; `customer_phone` required; `customer_nik` required digits:16; `ktp_photo`, `selfie_photo` required image; `agree_terms` accepted |
| Denda | `late_fee` required integer min:0 saat penyelesaian terlambat |

Tampilkan pesan error dalam Bahasa Indonesia (`lang/id/validation.php`, set `APP_LOCALE=id`).

---

## 11. Seeder

- `AdminSeeder`: buat satu akun `role = admin` (password di-hash otomatis oleh cast `hashed`).
- `BrandSeeder`: Toyota, Honda, Daihatsu, Suzuki, Mitsubishi.
- `CarTypeSeeder`: SUV, MPV, Sedan, Hatchback.
- Contoh data mobil (mis. Toyota Avanza, MPV, Rp 450.000/hari seperti pada antarmuka) boleh ditambah lewat `CarSeeder`.

---

## 12. Pemetaan Fitur → Implementasi

| Kebutuhan | Controller / Service | Tabel utama |
|---|---|---|
| F001 Daftar & login | `CustomerAuthController`, `AdminAuthController` | `account` |
| F002 Cari mobil (merk/jenis/harga) | `CatalogController@index` (scope filter pada `Car`) | `car`, `brand`, `car_type` |
| F003 Kelola katalog | `Admin\CarController` | `car`, `car_photo` |
| F004 Pesan mobil | `BookingController`, `RentalService::create` | `rental`, `add_on` |
| F005 Bayar tunai/QRIS | `PaymentController`, `PaymentService` | `payment`, `qris`, `cash` |
| F006 Konfirmasi + invoice + kode booking | `Admin\OrderController@confirmPayment` | `payment`, `rental` |
| F007 Riwayat | `HistoryController` (customer & admin) | `rental`, `booking_history` |
| F008 Pengembalian & penyelesaian | `Admin\OrderController@complete` | `rental`, `car` |
| F009 Logout | `CustomerAuthController@logout` | session |

Filter katalog F002 (contoh scope):
```php
public function scopeFilter($q, array $f) {
    return $q->when($f['brand_id'] ?? null, fn($q,$v) => $q->where('brand_id', $v))
             ->when($f['type_id']  ?? null, fn($q,$v) => $q->where('type_id', $v))
             ->when($f['min_price'] ?? null, fn($q,$v) => $q->where('daily_price', '>=', $v))
             ->when($f['max_price'] ?? null, fn($q,$v) => $q->where('daily_price', '<=', $v))
             ->when($f['q'] ?? null, fn($q,$v) => $q->where('model', 'like', "%$v%"));
}
```

---

## 13. Perintah Artisan Acuan

```bash
php artisan make:model Account -m          # ulangi untuk semua model; edit migration sesuai Bagian 3
php artisan make:middleware EnsureRole
php artisan make:request RegisterRequest   # dst.
php artisan make:command ExpirePayments --command=payments:expire
php artisan make:seeder AdminSeeder
php artisan storage:link

# data boleh dihapus:
php artisan migrate:fresh --seed
# data harus dipertahankan:
php artisan migrate                        # jalankan migration rename/alter yang baru
```

---

## 14. Checklist Hasil Akhir

- [ ] 11 tabel sesuai nama rancangan, dibuat lewat migration (bukan SQL mentah)
- [ ] Migration `users` bawaan dihapus, `config/auth.php` memakai model `Account`
- [ ] Semua model punya `$table`, `$primaryKey`; `add_on`, `qris`, `cash` memakai `$incrementing = false`
- [ ] `booking_history` memakai `history_id` + unique `(rental_id, sequence_no)`
- [ ] Middleware `role` terpasang; admin dan customer tidak bisa saling mengakses
- [ ] Customer hanya bisa melihat rental miliknya sendiri
- [ ] KTP dan swafoto disimpan di disk private dan hanya admin yang bisa melihat
- [ ] Setiap perubahan status rental mencatat `booking_history` dalam transaksi DB
- [ ] Cek ketersediaan memakai pengecekan tumpang tindih tanggal + `lockForUpdate`
- [ ] Scheduler `payments:expire` berjalan (QRIS 10 menit, tunai 3 jam)
- [ ] Seeder admin dan data master tersedia
- [ ] Tabel pemetaan nama lama → baru dan daftar asumsi sudah dikeluarkan
