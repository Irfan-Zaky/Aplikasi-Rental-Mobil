<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case QRIS = 'QRIS';
    case Tunai = 'Tunai';
}
