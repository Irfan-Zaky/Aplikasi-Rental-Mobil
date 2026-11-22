<?php

namespace App\Enums;

enum MobilStatus: string
{
    case Tersedia = 'Tersedia';
    case Disewa = 'Disewa';
    case Perbaikan = 'Perbaikan';
    case Nonaktif = 'Nonaktif';
}
