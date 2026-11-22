<?php

namespace Tests\Feature;

use App\Enums\MobilStatus;
use App\Enums\RentalStatus;
use App\Models\Rental;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RentalStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_transisi_status_menulis_riwayat_status(): void
    {
        $rental = Rental::factory()->create([
            'status' => RentalStatus::MenungguPembayaran,
            'customer_nik' => '1234567890123456',
        ]);

        $rental->transitionTo(RentalStatus::MenungguVerifikasi);

        $this->assertSame(RentalStatus::MenungguVerifikasi, $rental->fresh()->status);
        $this->assertDatabaseHas('booking_histories', [
            'rental_id' => $rental->id,
            'sequence_no' => 1,
            'status' => RentalStatus::MenungguVerifikasi->value,
        ]);
    }

    public function test_transisi_berjalan_menandai_mobil_disewa_dan_selesai_mengembalikannya(): void
    {
        $rental = Rental::factory()->create(['status' => RentalStatus::PembayaranBerhasil]);

        $rental->transitionTo(RentalStatus::Berjalan);

        $this->assertSame(MobilStatus::Disewa->value, $rental->mobil->fresh()->status);

        $rental->transitionTo(RentalStatus::Selesai);

        $this->assertSame(MobilStatus::Tersedia->value, $rental->mobil->fresh()->status);
        $this->assertDatabaseCount('booking_histories', 2);
    }

    public function test_transisi_di_luar_aturan_ditolak_tanpa_menulis_riwayat(): void
    {
        $rental = Rental::factory()->create(['status' => RentalStatus::MenungguPembayaran]);

        try {
            $rental->transitionTo(RentalStatus::Berjalan);
            $this->fail('Transisi tidak sah harus ditolak.');
        } catch (\InvalidArgumentException $exception) {
            $this->assertStringContainsString('Menunggu Pembayaran', $exception->getMessage());
        }

        $this->assertSame(RentalStatus::MenungguPembayaran, $rental->fresh()->status);
        $this->assertDatabaseCount('booking_histories', 0);
    }

    public function test_nik_penyewa_tersimpan_terenkripsi(): void
    {
        $rental = Rental::factory()->create(['customer_nik' => '1234567890123456']);

        $this->assertDatabaseMissing('rentals', [
            'id' => $rental->id,
            'customer_nik' => '1234567890123456',
        ]);
        $this->assertSame('1234567890123456', $rental->fresh()->customer_nik);
    }
}
