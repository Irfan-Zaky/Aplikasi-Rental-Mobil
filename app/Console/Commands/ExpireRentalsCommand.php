<?php

namespace App\Console\Commands;

use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use App\Enums\RentalStatus;
use App\Models\Rental;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

#[Signature('rentals:expire')]
#[Description('Membatalkan rental yang batas pembayarannya sudah terlewat.')]
class ExpireRentalsCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $now = now();
        $dibatalkan = 0;

        Rental::query()
            ->whereIn('status', [
                RentalStatus::MenungguPembayaran->value,
                RentalStatus::MenungguVerifikasi->value,
            ])
            ->whereHas('payments', function (Builder $query) use ($now): void {
                $query->where('type', PaymentType::Sewa->value)
                    ->where('status', PaymentStatus::MenungguPembayaran->value)
                    ->whereNotNull('expires_at')
                    ->where('expires_at', '<=', $now);
            })
            ->chunkById(100, function (Collection $rentals) use (&$dibatalkan, $now): void {
                foreach ($rentals as $rental) {
                    DB::transaction(function () use ($rental, $now, &$dibatalkan): void {
                        $rentalTerkunci = Rental::whereKey($rental->rental_id)
                            ->lockForUpdate()
                            ->first();

                        if ($rentalTerkunci === null) {
                            return;
                        }

                        if (! in_array($rentalTerkunci->status, [
                            RentalStatus::MenungguPembayaran,
                            RentalStatus::MenungguVerifikasi,
                        ], true)) {
                            return;
                        }

                        $paymentKedaluwarsa = $rentalTerkunci->payments()
                            ->where('type', PaymentType::Sewa->value)
                            ->where('status', PaymentStatus::MenungguPembayaran->value)
                            ->whereNotNull('expires_at')
                            ->where('expires_at', '<=', $now)
                            ->get();

                        if ($paymentKedaluwarsa->isEmpty()) {
                            return;
                        }

                        foreach ($paymentKedaluwarsa as $payment) {
                            $payment->update(['status' => PaymentStatus::Kadaluarsa]);
                        }

                        $rentalTerkunci->update(['cancelled_at' => $now]);

                        $rentalTerkunci->transitionTo(
                            RentalStatus::Dibatalkan,
                            null,
                            'Dibatalkan otomatis: batas pembayaran terlewat'
                        );

                        $dibatalkan++;
                    });
                }
            });

        $this->info("{$dibatalkan} rental dibatalkan otomatis.");

        return self::SUCCESS;
    }
}
