<?php

declare(strict_types=1);

namespace WFBP\Admin;

use WFBP\Core\Settings;

final class SettingsPage
{
    private Settings $settings;

    public function __construct(Settings $settings)
    {
        $this->settings = $settings;
    }

    public function register(): void
    {
        add_action('admin_menu', [$this, 'addMenu']);
        add_action('admin_init', [$this, 'registerSettings']);
    }

    public function addMenu(): void
    {
        add_options_page(
            __('Flight Booking', 'wfbp'),
            __('Flight Booking', 'wfbp'),
            'manage_options',
            'wfbp-settings',
            [$this, 'render']
        );
    }

    public function registerSettings(): void
    {
        register_setting('wfbp_settings_group', Settings::OPTION_KEY, [
            'type' => 'array',
            'sanitize_callback' => [$this, 'sanitize'],
            'default' => $this->settings->all(),
        ]);

        add_settings_section('wfbp_duffel', __('Duffel Configuration', 'wfbp'), '__return_empty_string', 'wfbp-settings');
        add_settings_section('wfbp_currency', __('Currency Configuration', 'wfbp'), '__return_empty_string', 'wfbp-settings');
        add_settings_section('wfbp_payments', __('Payment Providers', 'wfbp'), '__return_empty_string', 'wfbp-settings');
        add_settings_section('wfbp_features', __('Feature Flags', 'wfbp'), '__return_empty_string', 'wfbp-settings');

        $fields = [
            'duffel_api_token' => __('API Token', 'wfbp'),
            'duffel_environment' => __('Environment', 'wfbp'),
            'default_cabin_class' => __('Default Cabin Class', 'wfbp'),
            'debug_logging' => __('Debug Logging', 'wfbp'),
            'webhook_secret' => __('Webhook Secret', 'wfbp'),
            'display_currency' => __('Display Currency', 'wfbp'),
            'checkout_currency' => __('Checkout Currency', 'wfbp'),
            'manual_rates_json' => __('Manual Rates JSON', 'wfbp'),
        ];

        foreach ($fields as $key => $label) {
            add_settings_field($key, $label, [$this, 'renderField'], 'wfbp-settings', $key === 'display_currency' || $key === 'checkout_currency' || $key === 'manual_rates_json' ? 'wfbp_currency' : 'wfbp_duffel', ['key' => $key]);
        }

        add_settings_field('payment_providers', __('Providers', 'wfbp'), [$this, 'renderPayments'], 'wfbp-settings', 'wfbp_payments');
        add_settings_field('feature_flags', __('Flags', 'wfbp'), [$this, 'renderFeatures'], 'wfbp-settings', 'wfbp_features');
    }

    public function sanitize(array $input): array
    {
        if (! current_user_can('manage_options')) {
            return $this->settings->all();
        }

        $current = $this->settings->all();
        $allowedCurrencies = ['USD','NGN','KES','EUR','GBP','GHS','XOF','RWF','ZAR','CAD','JPY'];

        $current['duffel_api_token'] = sanitize_text_field($input['duffel_api_token'] ?? '');
        $current['duffel_environment'] = in_array(($input['duffel_environment'] ?? 'sandbox'), ['sandbox','live'], true) ? $input['duffel_environment'] : 'sandbox';
        $current['default_cabin_class'] = sanitize_key($input['default_cabin_class'] ?? 'economy');
        $current['debug_logging'] = ! empty($input['debug_logging']) ? 1 : 0;
        $current['display_currency'] = in_array(($input['display_currency'] ?? 'EUR'), $allowedCurrencies, true) ? $input['display_currency'] : 'EUR';
        $current['checkout_currency'] = in_array(($input['checkout_currency'] ?? 'EUR'), $allowedCurrencies, true) ? $input['checkout_currency'] : 'EUR';
        $current['manual_rates_json'] = wp_kses_post((string) ($input['manual_rates_json'] ?? '{}'));
        $current['webhook_secret'] = sanitize_text_field($input['webhook_secret'] ?? $current['webhook_secret']);

        $providers = $input['payment_providers'] ?? [];
        foreach (['paypal','paystack','stripe','flutterwave','bank_transfer'] as $provider) {
            $current['payment_providers'][$provider]['enabled'] = ! empty($providers[$provider]['enabled']) ? 1 : 0;
            if ('bank_transfer' === $provider) {
                $current['payment_providers'][$provider]['instructions'] = sanitize_textarea_field($providers[$provider]['instructions'] ?? '');
            } else {
                $current['payment_providers'][$provider]['checkout_url'] = esc_url_raw($providers[$provider]['checkout_url'] ?? '');
            }
        }

        $flags = $input['feature_flags'] ?? [];
        foreach (['roundtrip','multi_city','ancillaries','traveler_profiles'] as $flag) {
            $current['feature_flags'][$flag] = ! empty($flags[$flag]) ? 1 : 0;
        }

        return $current;
    }

    public function render(): void
    {
        if (! current_user_can('manage_options')) {
            return;
        }
        echo '<div class="wrap"><h1>' . esc_html__('Flight Booking Settings', 'wfbp') . '</h1>';
        echo '<form method="post" action="options.php">';
        settings_fields('wfbp_settings_group');
        do_settings_sections('wfbp-settings');
        submit_button();
        echo '</form></div>';
    }

    public function renderField(array $args): void
    {
        $key = $args['key'];
        $value = $this->settings->get($key, '');

        if ('duffel_environment' === $key) {
            echo '<select name="' . esc_attr(Settings::OPTION_KEY . '[' . $key . ']') . '">';
            foreach (['sandbox', 'live'] as $env) {
                printf('<option value="%1$s" %2$s>%1$s</option>', esc_attr($env), selected($value, $env, false));
            }
            echo '</select>';
            return;
        }

        if (in_array($key, ['display_currency', 'checkout_currency'], true)) {
            echo '<select name="' . esc_attr(Settings::OPTION_KEY . '[' . $key . ']') . '">';
            foreach (['USD','NGN','KES','EUR','GBP','GHS','XOF','RWF','ZAR','CAD','JPY'] as $currency) {
                printf('<option value="%1$s" %2$s>%1$s</option>', esc_attr($currency), selected($value, $currency, false));
            }
            echo '</select>';
            return;
        }

        if ('manual_rates_json' === $key) {
            echo '<textarea rows="5" cols="60" name="' . esc_attr(Settings::OPTION_KEY . '[' . $key . ']') . '">' . esc_textarea((string) $value) . '</textarea>';
            return;
        }

        if ('debug_logging' === $key) {
            printf('<label><input type="checkbox" name="%1$s" value="1" %2$s /> %3$s</label>', esc_attr(Settings::OPTION_KEY . '[' . $key . ']'), checked((int) $value, 1, false), esc_html__('Enable logging', 'wfbp'));
            return;
        }

        printf('<input type="text" class="regular-text" name="%1$s" value="%2$s" />', esc_attr(Settings::OPTION_KEY . '[' . $key . ']'), esc_attr((string) $value));
    }

    public function renderPayments(): void
    {
        $providers = (array) $this->settings->get('payment_providers', []);
        foreach (['paypal','paystack','stripe','flutterwave','bank_transfer'] as $provider) {
            $isBank = 'bank_transfer' === $provider;
            echo '<p><strong>' . esc_html(ucwords(str_replace('_', ' ', $provider))) . '</strong></p>';
            printf('<label><input type="checkbox" name="%1$s[payment_providers][%2$s][enabled]" value="1" %3$s /> %4$s</label><br>', esc_attr(Settings::OPTION_KEY), esc_attr($provider), checked((int) ($providers[$provider]['enabled'] ?? 0), 1, false), esc_html__('Enabled', 'wfbp'));
            if ($isBank) {
                printf('<textarea rows="3" cols="60" name="%1$s[payment_providers][%2$s][instructions]">%3$s</textarea>', esc_attr(Settings::OPTION_KEY), esc_attr($provider), esc_textarea((string) ($providers[$provider]['instructions'] ?? '')));
            } else {
                printf('<input type="url" class="regular-text" name="%1$s[payment_providers][%2$s][checkout_url]" value="%3$s" />', esc_attr(Settings::OPTION_KEY), esc_attr($provider), esc_attr((string) ($providers[$provider]['checkout_url'] ?? '')));
            }
        }
    }

    public function renderFeatures(): void
    {
        $flags = (array) $this->settings->get('feature_flags', []);
        foreach (['roundtrip','multi_city','ancillaries','traveler_profiles'] as $flag) {
            printf('<label style="display:block;"><input type="checkbox" name="%1$s[feature_flags][%2$s]" value="1" %3$s /> %4$s</label>', esc_attr(Settings::OPTION_KEY), esc_attr($flag), checked((int) ($flags[$flag] ?? 0), 1, false), esc_html(ucwords(str_replace('_', ' ', $flag))));
        }
    }
}
