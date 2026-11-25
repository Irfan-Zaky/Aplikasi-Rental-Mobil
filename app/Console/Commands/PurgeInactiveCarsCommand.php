<?php

namespace App\Console\Commands;

use App\Enums\CarStatus;
use App\Enums\RentalStatus;
use App\Models\Car;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

#[Signature('mobils:purge-inactive')]
#[Description('Menghapus mobil Nonaktif yang sudah tidak memiliki rental pemblokir.')]
class PurgeInactiveCarsCommand extends Command
{
    public function handle(): int
    {
        $statusPemblokir = collect(RentalStatus::cases())
            ->filter(fn (RentalStatus $status): bool => $status->isBlocking())
            ->map(fn (RentalStatus $status): string => $status->value)
            ->all();

        $dihapus = 0;

        Car::query()
            ->where('status', CarStatus::Nonaktif->value)
            ->whereDoesntHave('rentals', function (Builder $query) use ($statusPemblokir): void {
                $query->whereIn('status', $statusPemblokir);
            })
            ->chunkById(100, function (Collection $mobils) use (&$dihapus): void {
                foreach ($mobils as $mobil) {
                    $mobil->delete();
                    $dihapus++;
                }
            });

        $this->info("{$dihapus} mobil nonaktif dihapus.");

        return self::SUCCESS;
    }
}
