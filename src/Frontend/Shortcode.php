<?php

declare(strict_types=1);

namespace WFBP\Frontend;

final class Shortcode
{
    public function register(): void
    {
        add_shortcode('wfbp_search', [$this, 'renderSearch']);
        add_shortcode('wfbp_currency_switcher', [$this, 'renderCurrencySwitcher']);
    }

    public function renderSearch(): string
    {
        ob_start();
        include WFBP_PATH . 'templates/search-form.php';
        return (string) ob_get_clean();
    }

    public function renderCurrencySwitcher(): string
    {
        ob_start();
        include WFBP_PATH . 'templates/currency-switcher.php';
        return (string) ob_get_clean();
    }
}
