<?php

declare(strict_types=1);

namespace WFBP\API;

use WP_Error;
use WFBP\Core\Settings;

final class DuffelClient
{
    private Settings $settings;

    public function __construct(Settings $settings)
    {
        $this->settings = $settings;
    }

    public function request(string $method, string $endpoint, array $body = [], int $retries = 2): array|WP_Error
    {
        $baseUrl = $this->settings->get('duffel_environment') === 'live' ? 'https://api.duffel.com' : 'https://api.duffel.com';
        $url = trailingslashit($baseUrl) . ltrim($endpoint, '/');
        $token = (string) $this->settings->get('duffel_api_token', '');

        if ($token === '') {
            return new WP_Error('wfbp_missing_token', __('Duffel API token is not configured.', 'wfbp'));
        }

        $args = [
            'method' => strtoupper($method),
            'timeout' => 20,
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Duffel-Version' => 'v2',
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
            'body' => ! empty($body) ? wp_json_encode($body) : null,
        ];

        $attempt = 0;
        do {
            $response = wp_remote_request($url, $args);
            if (! is_wp_error($response)) {
                $code = (int) wp_remote_retrieve_response_code($response);
                $rawBody = (string) wp_remote_retrieve_body($response);
                $decoded = json_decode($rawBody, true);

                if ($code >= 200 && $code < 300) {
                    return is_array($decoded) ? $decoded : ['data' => $rawBody];
                }

                $message = is_array($decoded) ? ($decoded['errors'][0]['title'] ?? __('Unknown Duffel error.', 'wfbp')) : __('Duffel API returned an invalid response.', 'wfbp');
                if ($code >= 500 && $attempt < $retries) {
                    $attempt++;
                    continue;
                }

                return new WP_Error('wfbp_duffel_error', (string) $message, ['status' => $code]);
            }

            if ($attempt >= $retries) {
                return new WP_Error('wfbp_http_error', $response->get_error_message());
            }
            $attempt++;
        } while ($attempt <= $retries);

        return new WP_Error('wfbp_request_failed', __('Request failed after retries.', 'wfbp'));
    }

    public function createOfferRequest(array $payload): array|WP_Error
    {
        return $this->request('POST', '/air/offer_requests', $payload);
    }

    public function getOffers(string $offerRequestId): array|WP_Error
    {
        return $this->request('GET', '/air/offers?offer_request_id=' . rawurlencode($offerRequestId));
    }

    public function createOrder(array $payload): array|WP_Error
    {
        return $this->request('POST', '/air/orders', $payload);
    }

    public function getOrder(string $orderId): array|WP_Error
    {
        return $this->request('GET', '/air/orders/' . rawurlencode($orderId));
    }

    public function passthrough(string $endpoint, string $method = 'GET', array $payload = []): array|WP_Error
    {
        return $this->request($method, $endpoint, $payload);
    }
}
