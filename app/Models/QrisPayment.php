<?php

namespace App\Models;

use Database\Factories\QrisPaymentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QrisPayment extends Model
{
    /** @use HasFactory<QrisPaymentFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'payment_id',
        'qr_ref',
        'qr_code',
        'raw_payload',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'raw_payload' => 'array',
        ];
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }
}
