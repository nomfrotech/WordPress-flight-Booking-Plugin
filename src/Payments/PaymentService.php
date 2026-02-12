<?php

declare(strict_types=1);

namespace WFBP\Payments;

use WP_Error;
use WFBP\Core\Settings;
use WFBP\Currency\CurrencyService;
use WFBP\Repository\OrderRepository;
use WFBP\Repository\TransactionRepository;

final class PaymentService
{
    private Settings $settings;
    private CurrencyService $currency;
    private TransactionRepository $transactions;
    private OrderRepository $orders;

    public function __construct(Settings $settings, CurrencyService $currency)
    {
        $this->settings = $settings;
        $this->currency = $currency;
        $this->transactions = new TransactionRepository();
        $this->orders = new OrderRepository();
    }

    public function createCheckout(int $orderId, float $totalEur, string $provider, string $currency): array|WP_Error
    {
        $providers = (array) $this->settings->get('payment_providers', []);
        $providerConfig = (array) ($providers[$provider] ?? []);

        if (empty($providerConfig) || empty($providerConfig['enabled'])) {
            return new WP_Error('wfbp_provider_disabled', __('Selected payment provider is unavailable.', 'wfbp'));
        }

        if (! $this->hasRequiredCredentials($provider, $providerConfig)) {
            return new WP_Error('wfbp_provider_credentials_missing', __('Payment provider API credentials are missing.', 'wfbp'));
        }

        $converted = $this->currency->convertFromEur($totalEur, $currency);
        $reference = wp_generate_uuid4();

        $this->transactions->insert($orderId, $provider, strtoupper($currency), $converted, 'initiated', $reference);

        if ($provider === 'bank_transfer') {
            return [
                'provider' => 'bank_transfer',
                'instructions' => (string) ($providerConfig['instructions'] ?? ''),
                'reference' => $reference,
                'amount' => $converted,
                'currency' => strtoupper($currency),
            ];
        }

        $checkoutUrl = add_query_arg(
            [
                'ref' => rawurlencode($reference),
                'amount' => $converted,
                'currency' => strtoupper($currency),
                'order_id' => $orderId,
            ],
            (string) ($providerConfig['checkout_url'] ?? '')
        );

        return [
            'provider' => $provider,
            'checkout_url' => esc_url_raw($checkoutUrl),
            'reference' => $reference,
            'amount' => $converted,
            'currency' => strtoupper($currency),
            'eur_reference' => $totalEur,
        ];
    }

    public function verifyWebhookSignature(string $payload, string $signature): bool
    {
        $secret = (string) $this->settings->get('webhook_secret', '');
        if ($secret === '' || $signature === '') {
            return false;
        }

        $computed = hash_hmac('sha256', $payload, $secret);
        return hash_equals($computed, $signature);
    }

    public function processWebhook(array $payload): bool
    {
        $reference = sanitize_text_field((string) ($payload['reference'] ?? ''));
        $status = sanitize_text_field((string) ($payload['status'] ?? 'failed'));
        $duffelOrderId = sanitize_text_field((string) ($payload['duffel_order_id'] ?? ''));

        if ($reference === '' || $duffelOrderId === '') {
            return false;
        }

        $transactionUpdated = $this->transactions->updateStatusByReference($reference, $status);
        $orderUpdated = $this->orders->updatePaymentStatusByDuffelId($duffelOrderId, $status === 'paid' ? 'paid' : 'failed');

        return $transactionUpdated && $orderUpdated;
    }

    private function hasRequiredCredentials(string $provider, array $config): bool
    {
        $required = [
            'paypal' => ['client_id', 'client_secret'],
            'paystack' => ['public_key', 'secret_key'],
            'stripe' => ['publishable_key', 'secret_key'],
            'flutterwave' => ['public_key', 'secret_key'],
            'bank_transfer' => ['instructions'],
        ];

        foreach ($required[$provider] ?? [] as $key) {
            if (empty($config[$key])) {
                return false;
            }
        }

        return true;
    }
}
