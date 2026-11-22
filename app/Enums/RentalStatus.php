<?php

namespace App\Enums;

enum RentalStatus: string
{
    case MenungguPembayaran = 'Menunggu Pembayaran';
    case MenungguVerifikasi = 'Menunggu Verifikasi';
    case PembayaranBerhasil = 'Pembayaran Berhasil';
    case Berjalan = 'Berjalan';
    case Terlambat = 'Terlambat';
    case Selesai = 'Selesai';
    case Dibatalkan = 'Dibatalkan';

    /**
     * Status yang memblokir tanggal sewa mobil.
     */
    public function isBlocking(): bool
    {
        return match ($this) {
            self::MenungguPembayaran,
            self::MenungguVerifikasi,
            self::PembayaranBerhasil,
            self::Berjalan,
            self::Terlambat => true,
            self::Selesai,
            self::Dibatalkan => false,
        };
    }

    /**
     * Grup tampilan pada ringkasan dan tab riwayat.
     */
    public function group(): string
    {
        return match ($this) {
            self::PembayaranBerhasil,
            self::Berjalan,
            self::Terlambat => 'aktif',
            self::MenungguPembayaran,
            self::MenungguVerifikasi => 'menunggu',
            self::Selesai => 'selesai',
            self::Dibatalkan => 'dibatalkan',
        };
    }
}
