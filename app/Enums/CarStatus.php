<?php

namespace App\Enums;

enum CarStatus: string
{
    case Tersedia = 'Tersedia';
    case Disewa = 'Disewa';
    case Perbaikan = 'Perbaikan';
    case Nonaktif = 'Nonaktif';
}
