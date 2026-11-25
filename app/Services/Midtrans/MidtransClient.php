<?php

declare(strict_types=1);

namespace App\Services\Midtrans;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

final class MidtransClient
{
    /**
     * @param  array<string, mixed>  $customerDetails
     * @param  list<array<string, mixed>>  $itemDetails
     * @return array<string, mixed>
     */
    public function chargeQris(
        string $orderId,
        int $grossAmount,
        int $expiryMinutes,
        array $customerDetails,
        array $itemDetails,
    ): array {
        $payload = [
            'payment_type' => 'gopay',
            'transaction_details' => [
                'order_id' => $orderId,
                'gross_amount' => $grossAmount,
            ],
            'customer_details' => $customerDetails,
            'item_details' => $itemDetails,
            'gopay' => ['enable_callback' => false],
            'custom_expiry' => [
                'order_time' => now('Asia/Jakarta')->format('Y-m-d H:i:s O'),
                'expiry_duration' => $expiryMinutes,
                'unit' => 'minute',
            ],
        ];

        $response = $this->http()->post('/v2/charge', $payload);

        if ($response->failed() || ! in_array((string) $response->json('status_code'), ['200', '201'], true)) {
            logger()->error('Midtrans charge gagal', [
                'order_id' => $orderId,
                'status' => $response->status(),
            ]);

            throw new RuntimeException('Gagal membuat pembayaran QRIS.');
        }

        /** @var array<string, mixed> $data */
        $data = $response->json();

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    public function status(string $orderId): array
    {
        /** @var array<string, mixed> $data */
        $data = $this->http()
            ->get('/v2/'.$orderId.'/status')
            ->throw()
            ->json();

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    public function cancel(string $orderId): array
    {
        /** @var array<string, mixed> $data */
        $data = $this->http()
            ->post('/v2/'.$orderId.'/cancel')
            ->throw()
            ->json();

        return $data;
    }

    private function http(): PendingRequest
    {
        return Http::baseUrl((string) config('midtrans.base_url'))
            ->withBasicAuth((string) config('midtrans.server_key'), '')
            ->acceptJson()
            ->asJson()
            ->connectTimeout(3)
            ->timeout(10);
    }
}
