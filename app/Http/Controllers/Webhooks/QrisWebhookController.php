<?php

declare(strict_types=1);

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\Qris;
use App\Services\Midtrans\MidtransClient;
use App\Services\Payment\PaymentStatusUpdater;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

final class QrisWebhookController extends Controller
{
    public function __invoke(
        Request $request,
        MidtransClient $midtrans,
        PaymentStatusUpdater $updater,
    ): JsonResponse {
        $payload = $request->validate([
            'order_id' => ['required', 'string', 'max:100'],
            'status_code' => ['required', 'string'],
            'gross_amount' => ['required', 'string'],
            'signature_key' => ['required', 'string'],
            'transaction_status' => ['sometimes', 'string'],
        ]);

        $expected = hash(
            'sha512',
            $payload['order_id'].$payload['status_code'].$payload['gross_amount'].(string) config('midtrans.signature_secret_key'),
        );

        if (! hash_equals($expected, $payload['signature_key'])) {
            return response()->json(['message' => 'Signature tidak valid.'], 403);
        }

        $qris = Qris::where('qr_ref', $payload['order_id'])->firstOrFail();

        try {
            $statusPayload = $midtrans->status($qris->qr_ref);
        } catch (Throwable) {
            logger()->error('Gagal mengambil status Midtrans untuk webhook QRIS', [
                'order_id' => $qris->qr_ref,
            ]);

            return response()->json(['message' => 'Gagal memverifikasi status pembayaran.'], 502);
        }

        // Midtrans mengembalikan 407 untuk transaksi expire yang tetap valid di Get Status.
        if (! in_array((string) ($statusPayload['status_code'] ?? ''), ['200', '201', '407'], true)
            || ! filled($statusPayload['transaction_status'] ?? null)) {
            logger()->error('Status Midtrans tidak valid untuk webhook QRIS', [
                'order_id' => $qris->qr_ref,
                'status_code' => $statusPayload['status_code'] ?? null,
            ]);

            return response()->json(['message' => 'Gagal memverifikasi status pembayaran.'], 502);
        }

        $updater->applyMidtrans($qris->qr_ref, $statusPayload);

        return response()->json(['status' => 'ok']);
    }
}
