<?php

namespace App\Models;

use App\Enums\CarStatus;
use App\Enums\RentalStatus;
use Database\Factories\RentalFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;

/**
 * @property RentalStatus $status
 */
class Rental extends Model
{
    /** @use HasFactory<RentalFactory> */
    use HasFactory;

    /** @var string */
    protected $table = 'rental';

    /** @var string */
    protected $primaryKey = 'rental_id';

    /** @var list<string> */
    protected $fillable = [
        'trx_code',
        'booking_code',
        'invoice_number',
        'invoice_issued_at',
        'account_id',
        'car_id',
        'start_date',
        'end_date',
        'duration',
        'price_per_day',
        'subtotal',
        'total_rental',
        'late_fee',
        'self_driver',
        'customer_name',
        'customer_nik',
        'customer_phone',
        'customer_ktp_photo',
        'customer_selfie_photo',
        'terms_accepted_at',
        'status',
        'expires_at',
        'returned_at',
        'cancelled_at',
        'cancel_reason',
    ];

    /** @var list<string> */
    protected $hidden = ['customer_nik'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'start_date' => 'datetime',
            'end_date' => 'datetime',
            'invoice_issued_at' => 'datetime',
            'expires_at' => 'datetime',
            'returned_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'terms_accepted_at' => 'datetime',
            'duration' => 'integer',
            'price_per_day' => 'integer',
            'subtotal' => 'integer',
            'total_rental' => 'integer',
            'late_fee' => 'integer',
            'self_driver' => 'boolean',
            'customer_nik' => 'encrypted',
            'status' => RentalStatus::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    public function mobil(): BelongsTo
    {
        return $this->belongsTo(Car::class, 'car_id');
    }

    public function addOn(): HasOne
    {
        return $this->hasOne(AddOn::class, 'rental_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'rental_id');
    }

    public function bookingHistories(): HasMany
    {
        return $this->hasMany(BookingHistory::class, 'rental_id');
    }

    /**
     * Change status through the only allowed path and append a history row.
     */
    public function transitionTo(RentalStatus $status, ?Account $changedBy = null, ?string $note = null): void
    {
        $allowed = match ($this->status) {
            RentalStatus::MenungguPembayaran => [RentalStatus::MenungguVerifikasi, RentalStatus::Dibatalkan],
            RentalStatus::MenungguVerifikasi => [RentalStatus::PembayaranBerhasil, RentalStatus::Dibatalkan],
            RentalStatus::PembayaranBerhasil => [RentalStatus::Berjalan, RentalStatus::Dibatalkan],
            RentalStatus::Berjalan => [RentalStatus::Terlambat, RentalStatus::Selesai],
            RentalStatus::Terlambat => [RentalStatus::Selesai],
            RentalStatus::Selesai,
            RentalStatus::Dibatalkan => [],
        };

        if (! in_array($status, $allowed, true)) {
            throw new \InvalidArgumentException(
                "Transisi status {$this->status->value} ke {$status->value} tidak diizinkan."
            );
        }

        DB::transaction(function () use ($status, $changedBy, $note): void {
            $rental = self::whereKey($this->rental_id)->lockForUpdate()->firstOrFail();
            $sequenceNo = (int) $rental->bookingHistories()->max('sequence_no') + 1;

            $rental->status = $status;
            $rental->save();

            $rental->bookingHistories()->create([
                'sequence_no' => $sequenceNo,
                'status' => $status,
                'changed_by' => $changedBy?->account_id,
                'note' => $note,
                'changed_at' => now(),
            ]);

            if ($status === RentalStatus::Berjalan) {
                Car::whereKey($rental->car_id)->update(['status' => CarStatus::Disewa->value]);
            }

            if ($status === RentalStatus::Selesai) {
                Car::whereKey($rental->car_id)
                    ->where('status', CarStatus::Disewa->value)
                    ->update(['status' => CarStatus::Tersedia->value]);
            }

            $this->status = $status;
        });
    }
}
