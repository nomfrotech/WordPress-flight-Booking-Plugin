<?php

declare(strict_types=1);

namespace WFBP\Core;

final class Settings
{
    public const OPTION_KEY = 'wfbp_settings';

    /**
     * @return array<string,mixed>
     */
    public function all(): array
    {
        $defaults = [
            'duffel_api_token' => '',
            'duffel_environment' => 'sandbox',
            'default_cabin_class' => 'economy',
            'debug_logging' => 0,
            'display_currency' => 'EUR',
            'checkout_currency' => 'EUR',
            'manual_rates_json' => '{"USD":1.08,"NGN":1780,"KES":158,"EUR":1,"GBP":0.86,"GHS":17,"XOF":655,"RWF":1425,"ZAR":20.4,"CAD":1.47,"JPY":162}',
            'payment_providers' => [
                'paypal' => ['enabled' => 1, 'client_id' => '', 'client_secret' => '', 'checkout_url' => 'https://www.paypal.com/checkoutnow'],
                'paystack' => ['enabled' => 0, 'public_key' => '', 'secret_key' => '', 'checkout_url' => 'https://checkout.paystack.com'],
                'stripe' => ['enabled' => 0, 'publishable_key' => '', 'secret_key' => '', 'checkout_url' => 'https://checkout.stripe.com'],
                'flutterwave' => ['enabled' => 0, 'public_key' => '', 'secret_key' => '', 'checkout_url' => 'https://checkout.flutterwave.com/v3/hosted/pay'],
                'bank_transfer' => ['enabled' => 0, 'instructions' => ''],
            ],
            'feature_flags' => [
                'roundtrip' => 1,
                'multi_city' => 0,
                'ancillaries' => 0,
                'traveler_profiles' => 0,
            ],
            'webhook_secret' => wp_generate_password(32, false, false),
        ];

        $settings = get_option(self::OPTION_KEY, []);

        return wp_parse_args(is_array($settings) ? $settings : [], $defaults);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $settings = $this->all();
        return $settings[$key] ?? $default;
    }

    public function update(array $settings): bool
    {
        return update_option(self::OPTION_KEY, $settings, false);
    }
}
