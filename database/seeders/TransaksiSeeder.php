<?php

namespace Database\Seeders;

use App\Enums\HandoverMethod;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use App\Enums\RentalStatus;
use App\Models\Account;
use App\Models\AddOn;
use App\Models\BookingHistory;
use App\Models\Car;
use App\Models\Cash;
use App\Models\Payment;
use App\Models\Qris;
use App\Models\Rental;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TransaksiSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->pastikanFileDokumenDemo();

        // 1. Ambil atau buat Admin
        $admin = Account::firstOrCreate(
            ['email' => 'octaimub@gmail.com'],
            [
                'name' => 'octavia',
                'username' => 'octavia',
                'password' => Hash::make('ketuakita'),
                'email_verified_at' => now(),
                'role' => 'admin',
            ]
        );

        // 2. Ambil atau buat Customer
        $customerFadhil = Account::firstOrCreate(
            ['email' => 'fadhilkece@gmail.com'],
            [
                'name' => 'fadhil',
                'username' => 'fadhil',
                'no_hp' => '081234567890',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'role' => 'user',
            ]
        );

        $customerBudi = Account::firstOrCreate(
            ['email' => 'budi.santoso@example.com'],
            [
                'name' => 'Budi Santoso',
                'username' => 'budisantoso',
                'no_hp' => '081288402196',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'role' => 'user',
            ]
        );

        $customerSiti = Account::firstOrCreate(
            ['email' => 'siti.aminah@example.com'],
            [
                'name' => 'Siti Aminah',
                'username' => 'sitiaminah',
                'no_hp' => '081398765432',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'role' => 'user',
            ]
        );

        $customerAhmad = Account::firstOrCreate(
            ['email' => 'ahmad.ridwan@example.com'],
            [
                'name' => 'Ahmad Ridwan',
                'username' => 'ahmadridwan',
                'no_hp' => '085712345678',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'role' => 'user',
            ]
        );

        // 3. Pastikan ada data mobil dengan beragam merk & jenis
        $daftarCarData = [
            [
                'kode_mobil' => 'MOB-TYT-001',
                'merk' => 'Toyota',
                'nama_mobil' => 'Avanza 1.5 G',
                'jenis' => 'MPV',
                'status' => 'Tersedia',
                'harga_sewa' => 350000,
                'nomor_polisi' => 'B 1234 TAA',
                'spesifikasi' => 'Transmisi Manual, 7 Seater, AC Double Blower, Bensin',
            ],
            [
                'kode_mobil' => 'MOB-TYT-002',
                'merk' => 'Toyota',
                'nama_mobil' => 'Fortuner 2.8 VRZ',
                'jenis' => 'SUV',
                'status' => 'Disewa',
                'harga_sewa' => 950000,
                'nomor_polisi' => 'B 7777 TYT',
                'spesifikasi' => 'Transmisi Otomatis, 7 Seater, 4x2 Diesel, Panoramic Sunroof',
            ],
            [
                'kode_mobil' => 'MOB-HND-001',
                'merk' => 'Honda',
                'nama_mobil' => 'Brio RS',
                'jenis' => 'Hatchback',
                'status' => 'Tersedia',
                'harga_sewa' => 300000,
                'nomor_polisi' => 'B 2345 HND',
                'spesifikasi' => 'Transmisi CVT, 5 Seater, Irit Bensin, Bluetooth Audio',
            ],
            [
                'kode_mobil' => 'MOB-HND-002',
                'merk' => 'Honda',
                'nama_mobil' => 'HR-V SE',
                'jenis' => 'SUV',
                'status' => 'Disewa',
                'harga_sewa' => 600000,
                'nomor_polisi' => 'B 5678 HRV',
                'spesifikasi' => 'Transmisi CVT, 5 Seater, Honda Sensing, Bensin',
            ],
            [
                'kode_mobil' => 'MOB-DHT-001',
                'merk' => 'Daihatsu',
                'nama_mobil' => 'Xenia 1.3 R',
                'jenis' => 'MPV',
                'status' => 'Tersedia',
                'harga_sewa' => 320000,
                'nomor_polisi' => 'B 3456 DHT',
                'spesifikasi' => 'Transmisi Manual, 7 Seater, AC Digital, Bensin',
            ],
            [
                'kode_mobil' => 'MOB-DHT-002',
                'merk' => 'Daihatsu',
                'nama_mobil' => 'Terios Custom',
                'jenis' => 'SUV',
                'status' => 'Tersedia',
                'harga_sewa' => 450000,
                'nomor_polisi' => 'B 8901 TRS',
                'spesifikasi' => 'Transmisi Otomatis, 7 Seater, Ground Clearance Tinggi',
            ],
            [
                'kode_mobil' => 'MOB-MIT-001',
                'merk' => 'Mitsubishi',
                'nama_mobil' => 'Xpander Ultimate',
                'jenis' => 'MPV',
                'status' => 'Disewa',
                'harga_sewa' => 480000,
                'nomor_polisi' => 'B 4567 MIT',
                'spesifikasi' => 'Transmisi CVT, 7 Seater, Cruise Control, Suspensi Nyaman',
            ],
            [
                'kode_mobil' => 'MOB-SZK-001',
                'merk' => 'Suzuki',
                'nama_mobil' => 'Ertiga Hybrid Cruise',
                'jenis' => 'MPV',
                'status' => 'Tersedia',
                'harga_sewa' => 380000,
                'nomor_polisi' => 'B 6789 SZK',
                'spesifikasi' => 'Smart Hybrid, 7 Seater, Irit Bahan Bakar, Layar Sentuh',
            ],
            [
                'kode_mobil' => 'MOB-HYU-001',
                'merk' => 'Hyundai',
                'nama_mobil' => 'Stargazer Prime',
                'jenis' => 'MPV',
                'status' => 'Tersedia',
                'harga_sewa' => 470000,
                'nomor_polisi' => 'B 9012 HYU',
                'spesifikasi' => 'Captain Seat, 6 Seater, Bluelink, Hyundai SmartSense',
            ],
        ];

        $cars = [];
        foreach ($daftarCarData as $m) {
            $cars[$m['kode_mobil']] = Car::firstOrCreate(
                ['kode_mobil' => $m['kode_mobil']],
                $m
            );
        }

        // Hapus data rental contoh sebelumnya jika ada (agar seeder idempoten & bersih)
        // Namun kita jaga data real jika tidak ingin menghapus sembarangan
        Rental::query()->delete();

        // 4. Buat contoh transaksi
        // DATA KELOMPOK 1: TRANSAKSI BELUM SELESAI (Untuk Halaman Transaksi Admin)
        // =========================================================================

        // 1a. Belum Selesai - Menunggu Pembayaran (QRIS)
        $this->createRentalWithDetails([
            'trx_code' => 'TRX-202611220001',
            'user' => $customerFadhil,
            'mobil' => $cars['MOB-TYT-001'], // Toyota Avanza
            'start_date' => Carbon::now()->addDays(2)->startOfDay(),
            'end_date' => Carbon::now()->addDays(4)->endOfDay(),
            'duration' => 3,
            'price_per_day' => 350000,
            'subtotal' => 1050000,
            'driver_fee' => 0,
            'total_rental' => 1050000,
            'self_driver' => true,
            'customer_name' => $customerFadhil->name,
            'customer_phone' => $customerFadhil->no_hp,
            'status' => RentalStatus::MenungguPembayaran,
            'expires_at' => Carbon::now()->addMinutes(25),
            'handover_method' => HandoverMethod::AmbilDiGarasi->value,
            'pickup_point' => null,
            'payment_method' => PaymentMethod::QRIS,
            'payment_status' => PaymentStatus::MenungguPembayaran,
            'payment_expires_at' => Carbon::now()->addMinutes(10),
            'histories' => [
                [
                    'sequence_no' => 1,
                    'status' => RentalStatus::MenungguPembayaran,
                    'changed_by' => null,
                    'note' => 'Pemesanan dibuat oleh customer via QRIS',
                    'changed_at' => Carbon::now()->subMinutes(5),
                ],
            ],
        ]);

        // 1b. Belum Selesai - Menunggu Pembayaran (Tunai)
        $this->createRentalWithDetails([
            'trx_code' => 'TRX-202611220002',
            'user' => $customerBudi,
            'mobil' => $cars['MOB-DHT-001'], // Daihatsu Xenia
            'start_date' => Carbon::now()->addDays(1)->startOfDay(),
            'end_date' => Carbon::now()->addDays(2)->endOfDay(),
            'duration' => 2,
            'price_per_day' => 320000,
            'subtotal' => 640000,
            'driver_fee' => 0,
            'total_rental' => 640000,
            'self_driver' => true,
            'customer_name' => $customerBudi->name,
            'customer_phone' => $customerBudi->no_hp,
            'status' => RentalStatus::MenungguPembayaran,
            'expires_at' => Carbon::now()->addMinutes(30),
            'handover_method' => HandoverMethod::AmbilDiGarasi->value,
            'pickup_point' => null,
            'payment_method' => PaymentMethod::Tunai,
            'payment_code' => 'TNI-8840-2196',
            'payment_status' => PaymentStatus::MenungguPembayaran,
            'payment_expires_at' => Carbon::now()->addHours(3),
            'histories' => [
                [
                    'sequence_no' => 1,
                    'status' => RentalStatus::MenungguPembayaran,
                    'changed_by' => null,
                    'note' => 'Pemesanan dibuat oleh customer via Tunai',
                    'changed_at' => Carbon::now()->subMinutes(10),
                ],
            ],
        ]);

        // 1c. Belum Selesai - Menunggu Verifikasi (QRIS - webhook sukses, menunggu konfirmasi admin)
        $this->createRentalWithDetails([
            'trx_code' => 'TRX-202611220003',
            'user' => $customerSiti,
            'mobil' => $cars['MOB-HND-001'], // Honda Brio
            'start_date' => Carbon::now()->addDays(1)->startOfDay(),
            'end_date' => Carbon::now()->addDays(3)->endOfDay(),
            'duration' => 3,
            'price_per_day' => 300000,
            'subtotal' => 900000,
            'driver_fee' => 300000, // Dengan sopir (100k/hari x 3)
            'total_rental' => 1200000,
            'self_driver' => false,
            'customer_name' => $customerSiti->name,
            'customer_phone' => $customerSiti->no_hp,
            'status' => RentalStatus::MenungguVerifikasi,
            'expires_at' => Carbon::now()->addMinutes(20),
            'handover_method' => null,
            'pickup_point' => 'Bandara Hang Nadim, Batam',
            'payment_method' => PaymentMethod::QRIS,
            'payment_status' => PaymentStatus::MenungguKonfirmasi,
            'paid_at' => Carbon::now()->subMinutes(15),
            'histories' => [
                [
                    'sequence_no' => 1,
                    'status' => RentalStatus::MenungguPembayaran,
                    'changed_by' => null,
                    'note' => 'Pemesanan dibuat',
                    'changed_at' => Carbon::now()->subMinutes(25),
                ],
                [
                    'sequence_no' => 2,
                    'status' => RentalStatus::MenungguVerifikasi,
                    'changed_by' => null,
                    'note' => 'Pembayaran QRIS diterima dari gateway',
                    'changed_at' => Carbon::now()->subMinutes(15),
                ],
            ],
        ]);

        // 1d. Belum Selesai - Menunggu Verifikasi (Tunai - customer datang bawa kode)
        $this->createRentalWithDetails([
            'trx_code' => 'TRX-202611220004',
            'user' => $customerAhmad,
            'mobil' => $cars['MOB-SZK-001'], // Suzuki Ertiga
            'start_date' => Carbon::now()->addDays(2)->startOfDay(),
            'end_date' => Carbon::now()->addDays(4)->endOfDay(),
            'duration' => 3,
            'price_per_day' => 380000,
            'subtotal' => 1140000,
            'driver_fee' => 0,
            'total_rental' => 1140000,
            'self_driver' => true,
            'customer_name' => $customerAhmad->name,
            'customer_phone' => $customerAhmad->no_hp,
            'status' => RentalStatus::MenungguVerifikasi,
            'expires_at' => Carbon::now()->addHours(2),
            'handover_method' => HandoverMethod::Diantar->value,
            'pickup_point' => 'Hotel Aston Batam, Jl. Sriwijaya No. 1',
            'payment_method' => PaymentMethod::Tunai,
            'payment_code' => 'TNI-9820-1140',
            'payment_status' => PaymentStatus::MenungguKonfirmasi,
            'histories' => [
                [
                    'sequence_no' => 1,
                    'status' => RentalStatus::MenungguPembayaran,
                    'changed_by' => null,
                    'note' => 'Pemesanan dibuat dengan metode tunai',
                    'changed_at' => Carbon::now()->subMinutes(40),
                ],
                [
                    'sequence_no' => 2,
                    'status' => RentalStatus::MenungguVerifikasi,
                    'changed_by' => $admin->account_id,
                    'note' => 'Kode tunai divalidasi oleh admin di garasi',
                    'changed_at' => Carbon::now()->subMinutes(10),
                ],
            ],
        ]);

        // 1e. Belum Selesai - Pembayaran Berhasil (Booking code & invoice terbit, siap ambil)
        $this->createRentalWithDetails([
            'trx_code' => 'TRX-202611220005',
            'booking_code' => 'BK-104829',
            'invoice_number' => 'INV-2026-0001',
            'invoice_issued_at' => Carbon::now()->subHours(2),
            'user' => $customerBudi,
            'mobil' => $cars['MOB-TYT-001'], // Toyota Avanza
            'start_date' => Carbon::now()->addDay()->startOfDay(),
            'end_date' => Carbon::now()->addDays(3)->endOfDay(),
            'duration' => 3,
            'price_per_day' => 350000,
            'subtotal' => 1050000,
            'driver_fee' => 0,
            'total_rental' => 1050000,
            'self_driver' => true,
            'customer_name' => $customerBudi->name,
            'customer_phone' => $customerBudi->no_hp,
            'status' => RentalStatus::PembayaranBerhasil,
            'handover_method' => HandoverMethod::AmbilDiGarasi->value,
            'pickup_point' => null,
            'payment_method' => PaymentMethod::QRIS,
            'payment_status' => PaymentStatus::Berhasil,
            'paid_at' => Carbon::now()->subHours(2),
            'confirmed_by' => $admin->account_id,
            'confirmed_at' => Carbon::now()->subHours(2),
            'histories' => [
                [
                    'sequence_no' => 1,
                    'status' => RentalStatus::MenungguPembayaran,
                    'changed_by' => null,
                    'note' => 'Pemesanan dibuat',
                    'changed_at' => Carbon::now()->subHours(3),
                ],
                [
                    'sequence_no' => 2,
                    'status' => RentalStatus::MenungguVerifikasi,
                    'changed_by' => null,
                    'note' => 'Pembayaran QRIS sukses',
                    'changed_at' => Carbon::now()->subHours(2)->subMinutes(10),
                ],
                [
                    'sequence_no' => 3,
                    'status' => RentalStatus::PembayaranBerhasil,
                    'changed_by' => $admin->account_id,
                    'note' => 'Admin konfirmasi pembayaran',
                    'changed_at' => Carbon::now()->subHours(2),
                ],
            ],
        ]);

        // 1f. Belum Selesai - Berjalan (Unit di customer, siap pengembalian / filter penyelesaian)
        $this->createRentalWithDetails([
            'trx_code' => 'TRX-202611220006',
            'booking_code' => 'BK-205931',
            'invoice_number' => 'INV-2026-0002',
            'invoice_issued_at' => Carbon::now()->subDays(1),
            'user' => $customerFadhil,
            'mobil' => $cars['MOB-HND-002'], // Honda HR-V (status Disewa)
            'start_date' => Carbon::now()->subDay()->startOfDay(),
            'end_date' => Carbon::now()->addDays(2)->endOfDay(),
            'duration' => 4,
            'price_per_day' => 600000,
            'subtotal' => 2400000,
            'driver_fee' => 0,
            'total_rental' => 2400000,
            'self_driver' => true,
            'customer_name' => $customerFadhil->name,
            'customer_phone' => $customerFadhil->no_hp,
            'status' => RentalStatus::Berjalan,
            'handover_method' => HandoverMethod::Diantar->value,
            'pickup_point' => 'Perumahan Grand Batam City No. 12',
            'payment_method' => PaymentMethod::QRIS,
            'payment_status' => PaymentStatus::Berhasil,
            'paid_at' => Carbon::now()->subDays(1),
            'confirmed_by' => $admin->account_id,
            'confirmed_at' => Carbon::now()->subDays(1),
            'histories' => [
                [
                    'sequence_no' => 1,
                    'status' => RentalStatus::MenungguPembayaran,
                    'changed_by' => null,
                    'note' => 'Pemesanan dibuat',
                    'changed_at' => Carbon::now()->subDays(2),
                ],
                [
                    'sequence_no' => 2,
                    'status' => RentalStatus::PembayaranBerhasil,
                    'changed_by' => $admin->account_id,
                    'note' => 'Pembayaran dikonfirmasi admin',
                    'changed_at' => Carbon::now()->subDays(1),
                ],
                [
                    'sequence_no' => 3,
                    'status' => RentalStatus::Berjalan,
                    'changed_by' => null,
                    'note' => 'Waktu sewa dimulai, mobil diserahkan ke customer',
                    'changed_at' => Carbon::now()->subDay()->startOfDay(),
                ],
            ],
        ]);

        // 1g. Belum Selesai - Berjalan (Unit di customer, Tunai, filter penyelesaian)
        $this->createRentalWithDetails([
            'trx_code' => 'TRX-202611220007',
            'booking_code' => 'BK-304918',
            'invoice_number' => 'INV-2026-0003',
            'invoice_issued_at' => Carbon::now()->subDays(2),
            'user' => $customerAhmad,
            'mobil' => $cars['MOB-MIT-001'], // Mitsubishi Xpander (status Disewa)
            'start_date' => Carbon::now()->subDays(2)->startOfDay(),
            'end_date' => Carbon::now()->addDay()->endOfDay(),
            'duration' => 4,
            'price_per_day' => 480000,
            'subtotal' => 1920000,
            'driver_fee' => 0,
            'total_rental' => 1920000,
            'self_driver' => true,
            'customer_name' => $customerAhmad->name,
            'customer_phone' => $customerAhmad->no_hp,
            'status' => RentalStatus::Berjalan,
            'handover_method' => HandoverMethod::AmbilDiGarasi->value,
            'pickup_point' => null,
            'payment_method' => PaymentMethod::Tunai,
            'payment_code' => 'TNI-3049-1920',
            'payment_status' => PaymentStatus::Berhasil,
            'paid_at' => Carbon::now()->subDays(2),
            'confirmed_by' => $admin->account_id,
            'confirmed_at' => Carbon::now()->subDays(2),
            'histories' => [
                [
                    'sequence_no' => 1,
                    'status' => RentalStatus::MenungguPembayaran,
                    'changed_by' => null,
                    'note' => 'Pemesanan dibuat',
                    'changed_at' => Carbon::now()->subDays(3),
                ],
                [
                    'sequence_no' => 2,
                    'status' => RentalStatus::PembayaranBerhasil,
                    'changed_by' => $admin->account_id,
                    'note' => 'Pembayaran tunai diterima dan dikonfirmasi',
                    'changed_at' => Carbon::now()->subDays(2),
                ],
                [
                    'sequence_no' => 3,
                    'status' => RentalStatus::Berjalan,
                    'changed_by' => null,
                    'note' => 'Unit diambil di garasi oleh customer',
                    'changed_at' => Carbon::now()->subDays(2)->startOfDay(),
                ],
            ],
        ]);

        // 1h. Belum Selesai - Terlambat (Unit di customer, lewat tanggal end_date, filter penyelesaian)
        $this->createRentalWithDetails([
            'trx_code' => 'TRX-202611220008',
            'booking_code' => 'BK-409124',
            'invoice_number' => 'INV-2026-0004',
            'invoice_issued_at' => Carbon::now()->subDays(4),
            'user' => $customerSiti,
            'mobil' => $cars['MOB-TYT-002'], // Toyota Fortuner (status Disewa)
            'start_date' => Carbon::now()->subDays(4)->startOfDay(),
            'end_date' => Carbon::now()->subDay()->endOfDay(),
            'duration' => 4,
            'price_per_day' => 950000,
            'subtotal' => 3800000,
            'driver_fee' => 0,
            'total_rental' => 3800000,
            'self_driver' => true,
            'customer_name' => $customerSiti->name,
            'customer_phone' => $customerSiti->no_hp,
            'status' => RentalStatus::Terlambat,
            'handover_method' => HandoverMethod::AmbilDiGarasi->value,
            'pickup_point' => null,
            'payment_method' => PaymentMethod::QRIS,
            'payment_status' => PaymentStatus::Berhasil,
            'paid_at' => Carbon::now()->subDays(4),
            'confirmed_by' => $admin->account_id,
            'confirmed_at' => Carbon::now()->subDays(4),
            'histories' => [
                [
                    'sequence_no' => 1,
                    'status' => RentalStatus::MenungguPembayaran,
                    'changed_by' => null,
                    'note' => 'Pemesanan dibuat',
                    'changed_at' => Carbon::now()->subDays(5),
                ],
                [
                    'sequence_no' => 2,
                    'status' => RentalStatus::PembayaranBerhasil,
                    'changed_by' => $admin->account_id,
                    'note' => 'Pembayaran diverifikasi',
                    'changed_at' => Carbon::now()->subDays(4),
                ],
                [
                    'sequence_no' => 3,
                    'status' => RentalStatus::Berjalan,
                    'changed_by' => null,
                    'note' => 'Sewa dimulai',
                    'changed_at' => Carbon::now()->subDays(4)->startOfDay(),
                ],
                [
                    'sequence_no' => 4,
                    'status' => RentalStatus::Terlambat,
                    'changed_by' => null,
                    'note' => 'Melewati batas akhir pengembalian (terlambat 1 hari)',
                    'changed_at' => Carbon::now()->subDay()->endOfDay(),
                ],
            ],
        ]);

        // DATA KELOMPOK 2: TRANSAKSI SELESAI & DIBATALKAN (Untuk Halaman Riwayat Transaksi Admin & Customer)
        // ==================================================================================================

        // 2a. Selesai - Toyota Avanza (QRIS, Tepat Waktu) - customer Fadhil
        $this->createRentalWithDetails([
            'trx_code' => 'TRX-202611220009',
            'booking_code' => 'BK-501928',
            'invoice_number' => 'INV-2026-0005',
            'invoice_issued_at' => Carbon::now()->subDays(10),
            'user' => $customerFadhil,
            'mobil' => $cars['MOB-TYT-001'], // Toyota Avanza
            'start_date' => Carbon::now()->subDays(10)->startOfDay(),
            'end_date' => Carbon::now()->subDays(7)->endOfDay(),
            'duration' => 4,
            'price_per_day' => 350000,
            'subtotal' => 1400000,
            'driver_fee' => 0,
            'total_rental' => 1400000,
            'late_fee' => 0,
            'self_driver' => true,
            'customer_name' => $customerFadhil->name,
            'customer_phone' => $customerFadhil->no_hp,
            'status' => RentalStatus::Selesai,
            'returned_at' => Carbon::now()->subDays(7)->subHours(3),
            'handover_method' => HandoverMethod::AmbilDiGarasi->value,
            'pickup_point' => null,
            'payment_method' => PaymentMethod::QRIS,
            'payment_status' => PaymentStatus::Berhasil,
            'paid_at' => Carbon::now()->subDays(10),
            'confirmed_by' => $admin->account_id,
            'confirmed_at' => Carbon::now()->subDays(10),
            'histories' => [
                [
                    'sequence_no' => 1,
                    'status' => RentalStatus::MenungguPembayaran,
                    'changed_by' => null,
                    'note' => 'Pesanan dibuat',
                    'changed_at' => Carbon::now()->subDays(11),
                ],
                [
                    'sequence_no' => 2,
                    'status' => RentalStatus::PembayaranBerhasil,
                    'changed_by' => $admin->account_id,
                    'note' => 'Pembayaran dikonfirmasi admin',
                    'changed_at' => Carbon::now()->subDays(10),
                ],
                [
                    'sequence_no' => 3,
                    'status' => RentalStatus::Berjalan,
                    'changed_by' => null,
                    'note' => 'Car diambil di garasi',
                    'changed_at' => Carbon::now()->subDays(10)->startOfDay(),
                ],
                [
                    'sequence_no' => 4,
                    'status' => RentalStatus::Selesai,
                    'changed_by' => $admin->account_id,
                    'note' => 'Car dikembalikan tepat waktu, transaksi selesai',
                    'changed_at' => Carbon::now()->subDays(7)->subHours(3),
                ],
            ],
        ]);

        // 2b. Selesai - Honda Brio (Tunai, Ada Denda Keterlambatan) - customer Budi
        $this->createRentalWithDetails([
            'trx_code' => 'TRX-202611220010',
            'booking_code' => 'BK-602819',
            'invoice_number' => 'INV-2026-0006',
            'invoice_issued_at' => Carbon::now()->subDays(8),
            'user' => $customerBudi,
            'mobil' => $cars['MOB-HND-001'], // Honda Brio
            'start_date' => Carbon::now()->subDays(8)->startOfDay(),
            'end_date' => Carbon::now()->subDays(6)->endOfDay(),
            'duration' => 3,
            'price_per_day' => 300000,
            'subtotal' => 900000,
            'driver_fee' => 0,
            'total_rental' => 900000,
            'late_fee' => 150000, // Denda keterlambatan
            'self_driver' => true,
            'customer_name' => $customerBudi->name,
            'customer_phone' => $customerBudi->no_hp,
            'status' => RentalStatus::Selesai,
            'returned_at' => Carbon::now()->subDays(5)->startOfDay(), // Lewat 1 hari
            'handover_method' => HandoverMethod::Diantar->value,
            'pickup_point' => 'Batam Center Mall',
            'payment_method' => PaymentMethod::Tunai,
            'payment_code' => 'TNI-6028-0900',
            'payment_status' => PaymentStatus::Berhasil,
            'paid_at' => Carbon::now()->subDays(8),
            'confirmed_by' => $admin->account_id,
            'confirmed_at' => Carbon::now()->subDays(8),
            'extra_denda_payment' => 150000,
            'histories' => [
                [
                    'sequence_no' => 1,
                    'status' => RentalStatus::MenungguPembayaran,
                    'changed_by' => null,
                    'note' => 'Pesanan dibuat',
                    'changed_at' => Carbon::now()->subDays(9),
                ],
                [
                    'sequence_no' => 2,
                    'status' => RentalStatus::PembayaranBerhasil,
                    'changed_by' => $admin->account_id,
                    'note' => 'Konfirmasi pembayaran tunai',
                    'changed_at' => Carbon::now()->subDays(8),
                ],
                [
                    'sequence_no' => 3,
                    'status' => RentalStatus::Berjalan,
                    'changed_by' => null,
                    'note' => 'Car diserahkan',
                    'changed_at' => Carbon::now()->subDays(8)->startOfDay(),
                ],
                [
                    'sequence_no' => 4,
                    'status' => RentalStatus::Terlambat,
                    'changed_by' => null,
                    'note' => 'Melewati batas akhir pengembalian',
                    'changed_at' => Carbon::now()->subDays(6)->endOfDay(),
                ],
                [
                    'sequence_no' => 5,
                    'status' => RentalStatus::Selesai,
                    'changed_by' => $admin->account_id,
                    'note' => 'Car dikembalikan, denda Rp 150.000 dibayar lunas',
                    'changed_at' => Carbon::now()->subDays(5)->startOfDay(),
                ],
            ],
        ]);

        // 2c. Selesai - Daihatsu Xenia (QRIS) - customer Siti
        $this->createRentalWithDetails([
            'trx_code' => 'TRX-202611220011',
            'booking_code' => 'BK-703920',
            'invoice_number' => 'INV-2026-0007',
            'invoice_issued_at' => Carbon::now()->subDays(15),
            'user' => $customerSiti,
            'mobil' => $cars['MOB-DHT-001'], // Daihatsu Xenia
            'start_date' => Carbon::now()->subDays(15)->startOfDay(),
            'end_date' => Carbon::now()->subDays(12)->endOfDay(),
            'duration' => 4,
            'price_per_day' => 320000,
            'subtotal' => 1280000,
            'driver_fee' => 400000, // Dengan sopir
            'total_rental' => 1680000,
            'late_fee' => 0,
            'self_driver' => false,
            'customer_name' => $customerSiti->name,
            'customer_phone' => $customerSiti->no_hp,
            'status' => RentalStatus::Selesai,
            'returned_at' => Carbon::now()->subDays(12)->subHours(1),
            'handover_method' => null,
            'pickup_point' => 'Pelabuhan Batam Center',
            'payment_method' => PaymentMethod::QRIS,
            'payment_status' => PaymentStatus::Berhasil,
            'paid_at' => Carbon::now()->subDays(15),
            'confirmed_by' => $admin->account_id,
            'confirmed_at' => Carbon::now()->subDays(15),
            'histories' => [
                [
                    'sequence_no' => 1,
                    'status' => RentalStatus::PembayaranBerhasil,
                    'changed_by' => $admin->account_id,
                    'note' => 'Pembayaran terverifikasi',
                    'changed_at' => Carbon::now()->subDays(15),
                ],
                [
                    'sequence_no' => 2,
                    'status' => RentalStatus::Berjalan,
                    'changed_by' => null,
                    'note' => 'Layanan sopir dimulai',
                    'changed_at' => Carbon::now()->subDays(15)->startOfDay(),
                ],
                [
                    'sequence_no' => 3,
                    'status' => RentalStatus::Selesai,
                    'changed_by' => $admin->account_id,
                    'note' => 'Rental selesai',
                    'changed_at' => Carbon::now()->subDays(12)->subHours(1),
                ],
            ],
        ]);

        // 2d. Selesai - Hyundai Stargazer (QRIS) - customer Fadhil
        $this->createRentalWithDetails([
            'trx_code' => 'TRX-202611220012',
            'booking_code' => 'BK-804911',
            'invoice_number' => 'INV-2026-0008',
            'invoice_issued_at' => Carbon::now()->subDays(20),
            'user' => $customerFadhil,
            'mobil' => $cars['MOB-HYU-001'], // Hyundai Stargazer
            'start_date' => Carbon::now()->subDays(20)->startOfDay(),
            'end_date' => Carbon::now()->subDays(18)->endOfDay(),
            'duration' => 3,
            'price_per_day' => 470000,
            'subtotal' => 1410000,
            'driver_fee' => 0,
            'total_rental' => 1410000,
            'late_fee' => 0,
            'self_driver' => true,
            'customer_name' => $customerFadhil->name,
            'customer_phone' => $customerFadhil->no_hp,
            'status' => RentalStatus::Selesai,
            'returned_at' => Carbon::now()->subDays(18)->subHours(2),
            'handover_method' => HandoverMethod::AmbilDiGarasi->value,
            'pickup_point' => null,
            'payment_method' => PaymentMethod::QRIS,
            'payment_status' => PaymentStatus::Berhasil,
            'paid_at' => Carbon::now()->subDays(20),
            'confirmed_by' => $admin->account_id,
            'confirmed_at' => Carbon::now()->subDays(20),
            'histories' => [
                [
                    'sequence_no' => 1,
                    'status' => RentalStatus::PembayaranBerhasil,
                    'changed_by' => $admin->account_id,
                    'note' => 'Pembayaran dikonfirmasi',
                    'changed_at' => Carbon::now()->subDays(20),
                ],
                [
                    'sequence_no' => 2,
                    'status' => RentalStatus::Berjalan,
                    'changed_by' => null,
                    'note' => 'Car diserahkan',
                    'changed_at' => Carbon::now()->subDays(20)->startOfDay(),
                ],
                [
                    'sequence_no' => 3,
                    'status' => RentalStatus::Selesai,
                    'changed_by' => $admin->account_id,
                    'note' => 'Selesai dan mobil kembali ke garasi',
                    'changed_at' => Carbon::now()->subDays(18)->subHours(2),
                ],
            ],
        ]);

        // 2e. Selesai - Suzuki Ertiga (Tunai) - customer Ahmad
        $this->createRentalWithDetails([
            'trx_code' => 'TRX-202611220013',
            'booking_code' => 'BK-905182',
            'invoice_number' => 'INV-2026-0009',
            'invoice_issued_at' => Carbon::now()->subDays(25),
            'user' => $customerAhmad,
            'mobil' => $cars['MOB-SZK-001'], // Suzuki Ertiga
            'start_date' => Carbon::now()->subDays(25)->startOfDay(),
            'end_date' => Carbon::now()->subDays(23)->endOfDay(),
            'duration' => 3,
            'price_per_day' => 380000,
            'subtotal' => 1140000,
            'driver_fee' => 0,
            'total_rental' => 1140000,
            'late_fee' => 0,
            'self_driver' => true,
            'customer_name' => $customerAhmad->name,
            'customer_phone' => $customerAhmad->no_hp,
            'status' => RentalStatus::Selesai,
            'returned_at' => Carbon::now()->subDays(23)->subHours(4),
            'handover_method' => HandoverMethod::Diantar->value,
            'pickup_point' => 'Nagoya Hill Mall, Batam',
            'payment_method' => PaymentMethod::Tunai,
            'payment_code' => 'TNI-9051-1140',
            'payment_status' => PaymentStatus::Berhasil,
            'paid_at' => Carbon::now()->subDays(25),
            'confirmed_by' => $admin->account_id,
            'confirmed_at' => Carbon::now()->subDays(25),
            'histories' => [
                [
                    'sequence_no' => 1,
                    'status' => RentalStatus::PembayaranBerhasil,
                    'changed_by' => $admin->account_id,
                    'note' => 'Pembayaran tunai diterima',
                    'changed_at' => Carbon::now()->subDays(25),
                ],
                [
                    'sequence_no' => 2,
                    'status' => RentalStatus::Berjalan,
                    'changed_by' => null,
                    'note' => 'Unit diantar ke customer',
                    'changed_at' => Carbon::now()->subDays(25)->startOfDay(),
                ],
                [
                    'sequence_no' => 3,
                    'status' => RentalStatus::Selesai,
                    'changed_by' => $admin->account_id,
                    'note' => 'Unit diambil kembali oleh petugas, transaksi selesai',
                    'changed_at' => Carbon::now()->subDays(23)->subHours(4),
                ],
            ],
        ]);

        // 2f. Dibatalkan - Daihatsu Terios (Kadaluarsa pembayaran) - customer Budi
        $this->createRentalWithDetails([
            'trx_code' => 'TRX-202611220014',
            'user' => $customerBudi,
            'mobil' => $cars['MOB-DHT-002'], // Daihatsu Terios
            'start_date' => Carbon::now()->subDays(5)->startOfDay(),
            'end_date' => Carbon::now()->subDays(3)->endOfDay(),
            'duration' => 3,
            'price_per_day' => 450000,
            'subtotal' => 1350000,
            'driver_fee' => 0,
            'total_rental' => 1350000,
            'self_driver' => true,
            'customer_name' => $customerBudi->name,
            'customer_phone' => $customerBudi->no_hp,
            'status' => RentalStatus::Dibatalkan,
            'cancelled_at' => Carbon::now()->subDays(5)->addMinutes(30),
            'cancel_reason' => 'Pembayaran melewati batas waktu (Kadaluarsa)',
            'handover_method' => HandoverMethod::AmbilDiGarasi->value,
            'pickup_point' => null,
            'payment_method' => PaymentMethod::QRIS,
            'payment_status' => PaymentStatus::Kadaluarsa,
            'histories' => [
                [
                    'sequence_no' => 1,
                    'status' => RentalStatus::MenungguPembayaran,
                    'changed_by' => null,
                    'note' => 'Pesanan dibuat',
                    'changed_at' => Carbon::now()->subDays(5),
                ],
                [
                    'sequence_no' => 2,
                    'status' => RentalStatus::Dibatalkan,
                    'changed_by' => null,
                    'note' => 'Dibatalkan otomatis oleh sistem karena melewati batas pembayaran',
                    'changed_at' => Carbon::now()->subDays(5)->addMinutes(30),
                ],
            ],
        ]);

        // 2g. Dibatalkan - Toyota Fortuner (Dibatalkan Admin karena data tidak sesuai) - customer Fadhil
        $this->createRentalWithDetails([
            'trx_code' => 'TRX-202611220015',
            'user' => $customerFadhil,
            'mobil' => $cars['MOB-TYT-002'], // Toyota Fortuner
            'start_date' => Carbon::now()->subDays(3)->startOfDay(),
            'end_date' => Carbon::now()->subDays(1)->endOfDay(),
            'duration' => 3,
            'price_per_day' => 950000,
            'subtotal' => 2850000,
            'driver_fee' => 0,
            'total_rental' => 2850000,
            'self_driver' => true,
            'customer_name' => $customerFadhil->name,
            'customer_phone' => $customerFadhil->no_hp,
            'status' => RentalStatus::Dibatalkan,
            'cancelled_at' => Carbon::now()->subDays(3)->addHours(1),
            'cancel_reason' => 'Foto KTP tidak terbaca dan verifikasi identitas tidak sesuai',
            'handover_method' => HandoverMethod::AmbilDiGarasi->value,
            'pickup_point' => null,
            'payment_method' => PaymentMethod::QRIS,
            'payment_status' => PaymentStatus::Kadaluarsa,
            'histories' => [
                [
                    'sequence_no' => 1,
                    'status' => RentalStatus::MenungguPembayaran,
                    'changed_by' => null,
                    'note' => 'Pesanan dibuat',
                    'changed_at' => Carbon::now()->subDays(3),
                ],
                [
                    'sequence_no' => 2,
                    'status' => RentalStatus::Dibatalkan,
                    'changed_by' => $admin->account_id,
                    'note' => 'Foto KTP tidak terbaca dan verifikasi identitas tidak sesuai',
                    'changed_at' => Carbon::now()->subDays(3)->addHours(1),
                ],
            ],
        ]);
    }

    /**
     * Pastikan file dokumen contoh tersedia di disk private.
     */
    protected function pastikanFileDokumenDemo(): void
    {
        $dokumen = [
            'verifikasi/ktp/ktp_demo.jpg' => 'img_admin/detail-transaksi/Identity document.png',
            'verifikasi/selfie/selfie_demo.jpg' => 'img_admin/detail-transaksi/Selfie document.png',
        ];

        $disk = Storage::disk('local');

        foreach ($dokumen as $path => $sumber) {
            if (! $disk->exists($path)) {
                $disk->put($path, File::get(public_path($sumber)));
            }
        }
    }

    /**
     * Helper membuat 1 transaksi rental lengkap dengan relasi.
     *
     * @param  array<string, mixed>  $data
     */
    protected function createRentalWithDetails(array $data): Rental
    {
        $rental = Rental::create([
            'trx_code' => $data['trx_code'],
            'booking_code' => $data['booking_code'] ?? null,
            'invoice_number' => $data['invoice_number'] ?? null,
            'invoice_issued_at' => $data['invoice_issued_at'] ?? null,
            'account_id' => $data['user']->account_id,
            'car_id' => $data['mobil']->car_id,
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'duration' => $data['duration'],
            'price_per_day' => $data['price_per_day'],
            'subtotal' => $data['subtotal'],
            'total_rental' => $data['total_rental'],
            'late_fee' => $data['late_fee'] ?? 0,
            'self_driver' => $data['self_driver'],
            'customer_name' => $data['customer_name'],
            'customer_nik' => '3201'.fake()->numerify('############'),
            'customer_phone' => $data['customer_phone'],
            'customer_ktp_photo' => 'verifikasi/ktp/ktp_demo.jpg',
            'customer_selfie_photo' => 'verifikasi/selfie/selfie_demo.jpg',
            'terms_accepted_at' => Carbon::now()->subMinutes(60),
            'status' => $data['status'],
            'expires_at' => $data['expires_at'] ?? null,
            'returned_at' => $data['returned_at'] ?? null,
            'cancelled_at' => $data['cancelled_at'] ?? null,
            'cancel_reason' => $data['cancel_reason'] ?? null,
        ]);

        // Rental Add On
        AddOn::create([
            'rental_id' => $rental->rental_id,
            'handover_method' => $data['handover_method'] ?? null,
            'pickup_point' => $data['pickup_point'] ?? null,
            'driver_fee' => $data['driver_fee'] ?? 0,
            'driver_name' => empty($data['self_driver']) ? 'Sopir GoRental' : null,
            'driver_phone' => empty($data['self_driver']) ? '081299998888' : null,
        ]);

        // Payment Sewa
        $payment = Payment::create([
            'rental_id' => $rental->rental_id,
            'type' => PaymentType::Sewa,
            'method' => $data['payment_method'],
            'amount' => $data['total_rental'],
            'status' => $data['payment_status'],
            'expires_at' => $data['payment_expires_at'] ?? null,
            'paid_at' => $data['paid_at'] ?? null,
            'confirmed_by' => $data['confirmed_by'] ?? null,
            'confirmed_at' => $data['confirmed_at'] ?? null,
        ]);

        if ($data['payment_method'] === PaymentMethod::QRIS) {
            Qris::create([
                'payment_id' => $payment->payment_id,
                'qr_ref' => 'QR-'.Str::upper(Str::random(12)),
                'qr_code' => '00020101021226670016ID.CO.GORENTAL011893600911000'.$rental->rental_id,
            ]);
        } elseif ($data['payment_method'] === PaymentMethod::Tunai) {
            Cash::create([
                'payment_id' => $payment->payment_id,
                'payment_code' => $data['payment_code'] ?? 'TNI-'.fake()->numerify('####-####'),
            ]);
        }

        // Jika ada pembayaran denda tambahan
        if (! empty($data['extra_denda_payment'])) {
            Payment::create([
                'rental_id' => $rental->rental_id,
                'type' => PaymentType::Denda,
                'method' => PaymentMethod::Tunai,
                'amount' => $data['extra_denda_payment'],
                'status' => PaymentStatus::Berhasil,
                'paid_at' => $data['returned_at'] ?? Carbon::now(),
                'confirmed_by' => $data['confirmed_by'] ?? null,
                'confirmed_at' => $data['returned_at'] ?? Carbon::now(),
            ]);
        }

        // Booking Histories
        if (! empty($data['histories'])) {
            foreach ($data['histories'] as $hist) {
                BookingHistory::create([
                    'rental_id' => $rental->rental_id,
                    'sequence_no' => $hist['sequence_no'],
                    'status' => $hist['status'],
                    'changed_by' => $hist['changed_by'] ?? null,
                    'note' => $hist['note'] ?? null,
                    'changed_at' => $hist['changed_at'] ?? Carbon::now(),
                ]);
            }
        }

        return $rental;
    }
}
