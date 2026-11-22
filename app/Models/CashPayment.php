<?php

namespace App\Models;

use Database\Factories\CashPaymentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashPayment extends Model
{
    /** @use HasFactory<CashPaymentFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'payment_id',
        'payment_code',
    ];

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }
}
