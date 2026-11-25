<?php

declare(strict_types=1);

namespace App\Services\Payment;

use App\Enums\PaymentStatus;
use App\Enums\RentalStatus;
use App\Models\Payment;
use App\Models\Qris;
use App\Models\Rental;
use Illuminate\Support\Facades\DB;

final class PaymentStatusUpdater
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function applyMidtrans(string $orderId, array $payload): Payment
    {
        $qris = Qris::where('qr_ref', $orderId)->firstOrFail();

        return DB::transaction(function () use ($qris, $payload): Payment {
            $paymentAwal = Payment::findOrFail($qris->payment_id);
            $rental = Rental::whereKey($paymentAwal->rental_id)->lockForUpdate()->firstOrFail();
            $payment = Payment::whereKey($paymentAwal->payment_id)->lockForUpdate()->firstOrFail();

            $status = (string) ($payload['transaction_status'] ?? '');

            if ($payment->status !== PaymentStatus::MenungguPembayaran) {
                if ($status === 'settlement' && in_array($payment->status, [PaymentStatus::Gagal, PaymentStatus::Kadaluarsa], true)) {
                    logger()->critical('Settlement diterima untuk payment yang sudah ditutup', [
                        'payment_id' => $payment->payment_id,
                        'order_id' => $qris->qr_ref,
                    ]);
                }

                return $payment;
            }

            match ($status) {
                'settlement' => $this->tandaiMenungguKonfirmasi($payment, $rental),
                'deny', 'failure' => $payment->update(['status' => PaymentStatus::Gagal]),
                'expire', 'cancel' => $this->kedaluwarsa($payment, $rental),
                default => null,
            };

            $qris->update(['raw_payload' => $payload]);

            return $payment->refresh();
        });
    }

    private function tandaiMenungguKonfirmasi(Payment $payment, Rental $rental): void
    {
        $payment->update(['status' => PaymentStatus::MenungguKonfirmasi]);

        if ($rental->status === RentalStatus::MenungguPembayaran) {
            $rental->transitionTo(RentalStatus::MenungguVerifikasi, null, 'Webhook QRIS diterima');
        }
    }

    private function kedaluwarsa(Payment $payment, Rental $rental): void
    {
        $payment->update(['status' => PaymentStatus::Kadaluarsa]);

        if (in_array($rental->status, [RentalStatus::MenungguPembayaran, RentalStatus::MenungguVerifikasi], true)) {
            $rental->update(['cancelled_at' => now()]);
            $rental->transitionTo(RentalStatus::Dibatalkan, null, 'QRIS kedaluwarsa');
        }
    }
}
