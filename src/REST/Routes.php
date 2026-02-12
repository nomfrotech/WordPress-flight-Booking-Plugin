<?php

declare(strict_types=1);

namespace WFBP\REST;

use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WFBP\API\DuffelClient;
use WFBP\Booking\BookingService;
use WFBP\Core\Settings;
use WFBP\Payments\PaymentService;

final class Routes
{
    private BookingService $booking;
    private PaymentService $payments;
    private DuffelClient $duffel;
    private Settings $settings;

    public function __construct(BookingService $booking, PaymentService $payments, DuffelClient $duffel, Settings $settings)
    {
        $this->booking = $booking;
        $this->payments = $payments;
        $this->duffel = $duffel;
        $this->settings = $settings;
    }

    public function register(): void
    {
        add_action('rest_api_init', [$this, 'registerRoutes']);
    }

    public function registerRoutes(): void
    {
        register_rest_route('wfbp/v1', '/offers', [
            'methods' => 'POST',
            'callback' => [$this, 'offers'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route('wfbp/v1', '/orders', [
            'methods' => 'POST',
            'callback' => [$this, 'orders'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route('wfbp/v1', '/payments/webhook', [
            'methods' => 'POST',
            'callback' => [$this, 'webhook'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route('wfbp/v1', '/admin/passthrough', [
            'methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'],
            'callback' => [$this, 'passthrough'],
            'permission_callback' => static fn (): bool => current_user_can('manage_options'),
        ]);
    }

    public function offers(WP_REST_Request $request): WP_REST_Response
    {
        $payload = $request->get_json_params();
        $response = $this->booking->searchOffers(is_array($payload) ? $payload : []);
        if (is_wp_error($response)) {
            return new WP_REST_Response(['error' => $response->get_error_message()], 400);
        }

        return new WP_REST_Response($response, 200);
    }

    public function orders(WP_REST_Request $request): WP_REST_Response
    {
        $payload = $request->get_json_params();
        $data = is_array($payload) ? $payload : [];
        $response = $this->booking->createOrder($data['order'] ?? []);
        if (is_wp_error($response)) {
            return new WP_REST_Response(['error' => $response->get_error_message()], 400);
        }

        $checkout = $this->payments->createCheckout(
            (int) ($data['local_order_id'] ?? 0),
            (float) ($data['total_eur'] ?? 0),
            sanitize_key((string) ($data['provider'] ?? 'paypal')),
            sanitize_key((string) ($data['currency'] ?? 'EUR'))
        );

        return new WP_REST_Response(['order' => $response, 'checkout' => $checkout], 201);
    }

    public function webhook(WP_REST_Request $request): WP_REST_Response
    {
        $signature = (string) $request->get_header('x-wfbp-signature');
        $raw = $request->get_body();

        if (! $this->payments->verifyWebhookSignature($raw, $signature)) {
            return new WP_REST_Response(['error' => __('Invalid signature.', 'wfbp')], 403);
        }

        $payload = $request->get_json_params();
        $processed = $this->payments->processWebhook(is_array($payload) ? $payload : []);

        return new WP_REST_Response(['processed' => $processed], $processed ? 200 : 400);
    }

    public function passthrough(WP_REST_Request $request): WP_REST_Response
    {
        if (! wp_verify_nonce((string) $request->get_header('x-wp-nonce'), 'wp_rest')) {
            return new WP_REST_Response(['error' => __('Invalid nonce.', 'wfbp')], 403);
        }

        $endpoint = sanitize_text_field((string) $request->get_param('endpoint'));
        $method = sanitize_text_field((string) $request->get_method());
        $payload = $request->get_json_params();

        if ($endpoint === '') {
            return new WP_REST_Response(['error' => __('Endpoint is required.', 'wfbp')], 400);
        }

        $response = $this->duffel->passthrough($endpoint, $method, is_array($payload) ? $payload : []);
        if ($response instanceof WP_Error) {
            return new WP_REST_Response(['error' => $response->get_error_message()], 400);
        }

        return new WP_REST_Response($response, 200);
    }
}
