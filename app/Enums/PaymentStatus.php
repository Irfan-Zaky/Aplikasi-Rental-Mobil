<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case MenungguPembayaran = 'Menunggu Pembayaran';
    case MenungguKonfirmasi = 'Menunggu Konfirmasi';
    case Berhasil = 'Berhasil';
    case Gagal = 'Gagal';
    case Kadaluarsa = 'Kadaluarsa';
}
