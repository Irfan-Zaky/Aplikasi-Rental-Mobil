<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use Database\Factories\PaymentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property PaymentStatus $status
 * @property PaymentMethod $method
 * @property PaymentType $type
 */
class Payment extends Model
{
    /** @use HasFactory<PaymentFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'rental_id',
        'type',
        'method',
        'amount',
        'status',
        'expires_at',
        'paid_at',
        'confirmed_by',
        'confirmed_at',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'expires_at' => 'datetime',
            'paid_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'status' => PaymentStatus::class,
            'method' => PaymentMethod::class,
            'type' => PaymentType::class,
        ];
    }

    public function rental(): BelongsTo
    {
        return $this->belongsTo(Rental::class);
    }

    public function confirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    public function qris(): HasOne
    {
        return $this->hasOne(QrisPayment::class);
    }

    public function cash(): HasOne
    {
        return $this->hasOne(CashPayment::class);
    }
}
