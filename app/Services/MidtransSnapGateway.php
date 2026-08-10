<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class MidtransSnapGateway
{
    /**
     * @throws ConnectionException
     * @throws RequestException
     */
    public function createTransaction(Payment $payment, User $user, ?string $itemId = null): array
    {
        $this->validateKeyEnvironment();

        $response = Http::withBasicAuth($this->serverKey(), '')
            ->acceptJson()
            ->asJson()
            ->post($this->snapTransactionUrl(), [
                'transaction_details' => [
                    'order_id' => $payment->reference,
                    'gross_amount' => $payment->amount,
                ],
                'customer_details' => [
                    'first_name' => $payment->customer_name,
                    'email' => $payment->customer_email,
                    'phone' => $payment->customer_phone,
                ],
                'item_details' => [
                    [
                        'id' => $itemId ?: str($payment->package)->slug('-')->limit(45, '')->toString(),
                        'price' => $payment->amount,
                        'quantity' => 1,
                        'name' => $payment->package,
                    ],
                ],
                'callbacks' => [
                    'finish' => route('payment.midtrans.finish'),
                ],
            ])
            ->throw()
            ->json();

        if (blank($response['redirect_url'] ?? null)) {
            throw new RuntimeException('Midtrans did not return a checkout URL.');
        }

        return $response;
    }

    /**
     * @throws ConnectionException
     * @throws RequestException
     */
    public function transactionStatus(string $orderId): array
    {
        $this->validateKeyEnvironment();

        return Http::withBasicAuth($this->serverKey(), '')
            ->acceptJson()
            ->get($this->statusUrl($orderId))
            ->throw()
            ->json();
    }

    public function notificationSignatureIsValid(array $payload): bool
    {
        foreach (['order_id', 'status_code', 'gross_amount', 'signature_key'] as $key) {
            if (blank($payload[$key] ?? null)) {
                return false;
            }
        }

        $signature = hash(
            'sha512',
            $payload['order_id'] . $payload['status_code'] . $payload['gross_amount'] . $this->serverKey(),
        );

        return hash_equals($signature, (string) $payload['signature_key']);
    }

    public function serverKey(): string
    {
        $key = $this->storedSetting('midtrans_server_key')
            ?: $this->storedSetting('merchant_key')
            ?: config('services.midtrans.server_key');

        if (blank($key) || in_array($key, ['hm-production-key', 'your-midtrans-server-key'], true)) {
            throw new RuntimeException('Midtrans Server Key is not configured.');
        }

        return (string) $key;
    }

    public function environmentLabel(): string
    {
        return $this->isProduction() ? 'Production' : 'Sandbox';
    }

    public function validateKeyEnvironment(): void
    {
        $serverKey = $this->serverKey();
        $clientKey = $this->clientKey();

        if (blank($serverKey)) {
            throw new RuntimeException('Midtrans Server Key is not configured.');
        }

        if (blank($clientKey)) {
            throw new RuntimeException('Midtrans Client Key is not configured.');
        }

        if ($this->isProduction() && $this->looksLikeLegacySandboxKey($serverKey)) {
            throw new RuntimeException('Midtrans production mode is active, but the Server Key looks like a legacy Sandbox key.');
        }

        if ($this->isProduction() && $this->looksLikeLegacySandboxKey($clientKey)) {
            throw new RuntimeException('Midtrans production mode is active, but the Client Key looks like a legacy Sandbox key.');
        }
    }

    public function paymentAmountMatches(Payment $payment, array $payload): bool
    {
        if (! isset($payload['gross_amount'])) {
            return false;
        }

        return number_format((float) $payload['gross_amount'], 2, '.', '')
            === number_format($payment->amount, 2, '.', '');
    }

    public function completedStatus(array $payload): bool
    {
        $transactionStatus = $payload['transaction_status'] ?? null;
        $fraudStatus = $payload['fraud_status'] ?? null;

        return $transactionStatus === 'settlement'
            || ($transactionStatus === 'capture' && in_array($fraudStatus, [null, 'accept'], true));
    }

    public function failedStatus(array $payload): ?string
    {
        return match ($payload['transaction_status'] ?? null) {
            'deny' => 'Failed',
            'cancel' => 'Cancelled',
            'expire' => 'Expired',
            default => null,
        };
    }

    private function snapTransactionUrl(): string
    {
        return $this->isProduction()
            ? 'https://app.midtrans.com/snap/v1/transactions'
            : 'https://app.sandbox.midtrans.com/snap/v1/transactions';
    }

    private function statusUrl(string $orderId): string
    {
        $baseUrl = $this->isProduction()
            ? 'https://api.midtrans.com/v2'
            : 'https://api.sandbox.midtrans.com/v2';

        return $baseUrl . '/' . rawurlencode($orderId) . '/status';
    }

    private function isProduction(): bool
    {
        $storedMode = $this->storedSetting('midtrans_is_production');

        if ($storedMode !== null) {
            return $storedMode === '1';
        }

        $configuredMode = config('services.midtrans.is_production');

        if ($configuredMode !== null) {
            return filter_var($configuredMode, FILTER_VALIDATE_BOOLEAN);
        }

        return false;
    }

    private function clientKey(): string
    {
        return (string) ($this->storedSetting('midtrans_client_key') ?: config('services.midtrans.client_key'));
    }

    private function storedSetting(string $key): ?string
    {
        $value = SiteSetting::query()->where('key', $key)->value('value');

        return filled($value) ? (string) $value : null;
      }

    private function looksLikeLegacySandboxKey(string $key): bool
    {
        return str_starts_with($key, 'SB-');
    }
}
