<?php

namespace App\Console\Commands;

use App\Enums\RentalStatus;
use App\Models\Rental;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

#[Signature('rentals:tick')]
#[Description('Memulai sewa yang sudah tiba dan menandai sewa yang lewat batas waktu.')]
class RentalsTickCommand extends Command
{
    public function handle(): int
    {
        $now = now();
        $berjalan = 0;
        $terlambat = 0;

        Rental::query()
            ->whereIn('status', [RentalStatus::PembayaranBerhasil, RentalStatus::Berjalan])
            ->chunkById(100, function (Collection $rentals) use ($now, &$berjalan, &$terlambat): void {
                foreach ($rentals as $rental) {
                    DB::transaction(function () use ($rental, $now, &$berjalan, &$terlambat): void {
                        $rentalTerkunci = Rental::whereKey($rental->rental_id)
                            ->lockForUpdate()
                            ->first();

                        if ($rentalTerkunci === null) {
                            return;
                        }

                        if (
                            $rentalTerkunci->status === RentalStatus::PembayaranBerhasil
                            && $rentalTerkunci->start_date->lessThanOrEqualTo($now)
                        ) {
                            $rentalTerkunci->transitionTo(RentalStatus::Berjalan, null, 'Sewa dimulai otomatis');
                            $berjalan++;

                            return;
                        }

                        if (
                            $rentalTerkunci->status === RentalStatus::Berjalan
                            && $rentalTerkunci->end_date->lessThan($now)
                        ) {
                            $rentalTerkunci->transitionTo(RentalStatus::Terlambat, null, 'Batas pengembalian terlewat');
                            $terlambat++;
                        }
                    });
                }
            });

        $this->info("{$berjalan} sewa dimulai, {$terlambat} sewa terlambat.");

        return self::SUCCESS;
    }
}
