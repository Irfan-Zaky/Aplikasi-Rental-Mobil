<?php

namespace App\Models;

use App\Enums\HandoverMethod;
use Database\Factories\RentalAddOnFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property HandoverMethod|null $handover_method
 */
class RentalAddOn extends Model
{
    /** @use HasFactory<RentalAddOnFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'rental_id',
        'handover_method',
        'pickup_point',
        'driver_fee',
        'driver_name',
        'driver_phone',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'driver_fee' => 'integer',
            'handover_method' => HandoverMethod::class,
        ];
    }

    public function rental(): BelongsTo
    {
        return $this->belongsTo(Rental::class);
    }
}
