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
        add_settings_section('wfbp_payments', __('Payment Providers & API Keys', 'wfbp'), '__return_empty_string', 'wfbp-settings');
        add_settings_section('wfbp_features', __('Feature Flags', 'wfbp'), '__return_empty_string', 'wfbp-settings');

        foreach ([
            'duffel_api_token' => __('API Token', 'wfbp'),
            'duffel_environment' => __('Environment', 'wfbp'),
            'default_cabin_class' => __('Default Cabin Class', 'wfbp'),
            'debug_logging' => __('Debug Logging', 'wfbp'),
            'webhook_secret' => __('Webhook Secret', 'wfbp'),
            'display_currency' => __('Display Currency', 'wfbp'),
            'checkout_currency' => __('Checkout Currency', 'wfbp'),
            'manual_rates_json' => __('Manual Rates JSON', 'wfbp'),
        ] as $key => $label) {
            add_settings_field($key, $label, [$this, 'renderField'], 'wfbp-settings', in_array($key, ['display_currency', 'checkout_currency', 'manual_rates_json'], true) ? 'wfbp_currency' : 'wfbp_duffel', ['key' => $key]);
        }

        add_settings_field('payment_providers', __('Provider Credentials', 'wfbp'), [$this, 'renderPayments'], 'wfbp-settings', 'wfbp_payments');
        add_settings_field('feature_flags', __('Flags', 'wfbp'), [$this, 'renderFeatures'], 'wfbp-settings', 'wfbp_features');
    }

    public function sanitize(array $input): array
    {
        if (! current_user_can('manage_options')) {
            return $this->settings->all();
        }

        $current = $this->settings->all();
        $allowedCurrencies = ['USD','NGN','KES','EUR','GBP','GHS','XOF','RWF','ZAR','CAD','JPY'];

        $current['duffel_api_token'] = sanitize_text_field((string) ($input['duffel_api_token'] ?? ''));
        $current['duffel_environment'] = in_array((string) ($input['duffel_environment'] ?? 'sandbox'), ['sandbox','live'], true) ? (string) $input['duffel_environment'] : 'sandbox';
        $current['default_cabin_class'] = sanitize_key((string) ($input['default_cabin_class'] ?? 'economy'));
        $current['debug_logging'] = ! empty($input['debug_logging']) ? 1 : 0;
        $current['display_currency'] = in_array((string) ($input['display_currency'] ?? 'EUR'), $allowedCurrencies, true) ? (string) $input['display_currency'] : 'EUR';
        $current['checkout_currency'] = in_array((string) ($input['checkout_currency'] ?? 'EUR'), $allowedCurrencies, true) ? (string) $input['checkout_currency'] : 'EUR';
        $current['manual_rates_json'] = wp_kses_post((string) ($input['manual_rates_json'] ?? '{}'));
        $current['webhook_secret'] = sanitize_text_field((string) ($input['webhook_secret'] ?? $current['webhook_secret']));

        $providers = is_array($input['payment_providers'] ?? null) ? $input['payment_providers'] : [];
        foreach (['paypal','paystack','stripe','flutterwave','bank_transfer'] as $provider) {
            $current['payment_providers'][$provider]['enabled'] = ! empty($providers[$provider]['enabled']) ? 1 : 0;
            if ($provider === 'bank_transfer') {
                $current['payment_providers'][$provider]['instructions'] = sanitize_textarea_field((string) ($providers[$provider]['instructions'] ?? ''));
                continue;
            }

            $current['payment_providers'][$provider]['checkout_url'] = esc_url_raw((string) ($providers[$provider]['checkout_url'] ?? ''));

            foreach (['client_id', 'client_secret', 'public_key', 'secret_key', 'publishable_key'] as $field) {
                if (isset($providers[$provider][$field])) {
                    $current['payment_providers'][$provider][$field] = sanitize_text_field((string) $providers[$provider][$field]);
                }
            }
        }

        $flags = is_array($input['feature_flags'] ?? null) ? $input['feature_flags'] : [];
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
        echo '<p>' . esc_html__('Configure Duffel, currency, and payment API credentials for live checkout integration.', 'wfbp') . '</p>';
        echo '<form method="post" action="options.php">';
        settings_fields('wfbp_settings_group');
        do_settings_sections('wfbp-settings');
        submit_button();
        echo '</form></div>';
    }

    public function renderField(array $args): void
    {
        $key = (string) $args['key'];
        $value = $this->settings->get($key, '');

        if ($key === 'duffel_environment') {
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

        if ($key === 'manual_rates_json') {
            echo '<textarea rows="6" cols="70" name="' . esc_attr(Settings::OPTION_KEY . '[' . $key . ']') . '">' . esc_textarea((string) $value) . '</textarea>';
            return;
        }

        if ($key === 'debug_logging') {
            printf('<label><input type="checkbox" name="%1$s" value="1" %2$s /> %3$s</label>', esc_attr(Settings::OPTION_KEY . '[' . $key . ']'), checked((int) $value, 1, false), esc_html__('Enable detailed API logs', 'wfbp'));
            return;
        }

        printf('<input type="text" class="regular-text" name="%1$s" value="%2$s" />', esc_attr(Settings::OPTION_KEY . '[' . $key . ']'), esc_attr((string) $value));
    }

    public function renderPayments(): void
    {
        $providers = (array) $this->settings->get('payment_providers', []);
        $labels = [
            'paypal' => ['client_id', 'client_secret', 'checkout_url'],
            'paystack' => ['public_key', 'secret_key', 'checkout_url'],
            'stripe' => ['publishable_key', 'secret_key', 'checkout_url'],
            'flutterwave' => ['public_key', 'secret_key', 'checkout_url'],
            'bank_transfer' => ['instructions'],
        ];

        foreach ($labels as $provider => $fields) {
            echo '<fieldset style="margin-bottom:16px;padding:12px;border:1px solid #ccd0d4;background:#fff">';
            echo '<legend><strong>' . esc_html(ucwords(str_replace('_', ' ', $provider))) . '</strong></legend>';
            printf('<label><input type="checkbox" name="%1$s[payment_providers][%2$s][enabled]" value="1" %3$s /> %4$s</label><br><br>', esc_attr(Settings::OPTION_KEY), esc_attr($provider), checked((int) ($providers[$provider]['enabled'] ?? 0), 1, false), esc_html__('Enabled', 'wfbp'));

            foreach ($fields as $field) {
                $name = Settings::OPTION_KEY . '[payment_providers][' . $provider . '][' . $field . ']';
                $value = (string) ($providers[$provider][$field] ?? '');
                $label = ucwords(str_replace('_', ' ', $field));

                if ($field === 'instructions') {
                    echo '<p><label>' . esc_html($label) . '<br>';
                    echo '<textarea rows="4" cols="70" name="' . esc_attr($name) . '">' . esc_textarea($value) . '</textarea></label></p>';
                } else {
                    echo '<p><label>' . esc_html($label) . '<br>';
                    echo '<input type="text" class="regular-text" name="' . esc_attr($name) . '" value="' . esc_attr($value) . '"></label></p>';
                }
            }
            echo '</fieldset>';
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
