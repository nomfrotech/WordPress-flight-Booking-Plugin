<?php

declare(strict_types=1);

namespace WFBP\Currency;

use WFBP\Core\Settings;

final class CurrencyService
{
    private const CACHE_KEY = 'wfbp_currency_rates';
    private const CACHE_TTL = 1800;

    private Settings $settings;

    public function __construct(Settings $settings)
    {
        $this->settings = $settings;
    }

    /** @return array<string,float> */
    public function getRates(): array
    {
        $cached = get_transient(self::CACHE_KEY);
        if (is_array($cached)) {
            return $cached;
        }

        $raw = (string) $this->settings->get('manual_rates_json', '{}');
        $decoded = json_decode($raw, true);
        $rates = ['EUR' => 1.0];
        if (is_array($decoded)) {
            foreach ($decoded as $currency => $rate) {
                $rate = (float) $rate;
                if ($rate > 0) {
                    $rates[strtoupper((string) $currency)] = $rate;
                }
            }
        }

        set_transient(self::CACHE_KEY, $rates, self::CACHE_TTL);
        return $rates;
    }

    public function convertFromEur(float $amountEur, string $toCurrency): float
    {
        $rates = $this->getRates();
        $currency = strtoupper($toCurrency);
        $rate = $rates[$currency] ?? 1.0;
        return round($amountEur * $rate, 2);
    }

    public function format(float $amount, string $currency): string
    {
        return sprintf('%s %s', strtoupper($currency), number_format_i18n($amount, 2));
    }

    /** @return array<string,mixed> */
    public function frontendMeta(): array
    {
        $display = (string) $this->settings->get('display_currency', 'EUR');
        $checkout = (string) $this->settings->get('checkout_currency', 'EUR');
        return [
            'base' => 'EUR',
            'display' => strtoupper($display),
            'checkout' => strtoupper($checkout),
            'rates' => $this->getRates(),
        ];
    }
}
