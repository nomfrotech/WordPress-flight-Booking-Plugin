<?php

declare(strict_types=1);

namespace WFBP\Frontend;

use WFBP\Core\Settings;
use WFBP\Currency\CurrencyService;

final class Assets
{
    private Settings $settings;
    private CurrencyService $currency;

    public function __construct(Settings $settings, CurrencyService $currency)
    {
        $this->settings = $settings;
        $this->currency = $currency;
    }

    public function register(): void
    {
        add_action('wp_enqueue_scripts', [$this, 'enqueue']);
    }

    public function enqueue(): void
    {
        wp_enqueue_style('wfbp-frontend', WFBP_URL . 'assets/css/frontend.css', [], WFBP_VERSION);
        wp_enqueue_script('wfbp-frontend', WFBP_URL . 'assets/js/frontend.js', [], WFBP_VERSION, true);

        wp_localize_script('wfbp-frontend', 'wfbpConfig', [
            'restBase' => esc_url_raw(rest_url('wfbp/v1')),
            'nonce' => wp_create_nonce('wp_rest'),
            'currency' => $this->currency->frontendMeta(),
            'features' => $this->settings->get('feature_flags', []),
            'providers' => $this->settings->get('payment_providers', []),
            'i18n' => [
                'loading' => __('Searching flights...', 'wfbp'),
                'noOffers' => __('No offers found for this route.', 'wfbp'),
                'selectFlight' => __('Select this flight', 'wfbp'),
                'checkout' => __('Checkout', 'wfbp'),
            ],
        ]);
    }
}
