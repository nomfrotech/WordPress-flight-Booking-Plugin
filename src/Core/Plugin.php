<?php

declare(strict_types=1);

namespace WFBP\Core;

use WFBP\Admin\SettingsPage;
use WFBP\API\DuffelClient;
use WFBP\Booking\BookingService;
use WFBP\Currency\CurrencyService;
use WFBP\Elementor\WidgetRegistrar;
use WFBP\Frontend\Assets;
use WFBP\Frontend\Shortcode;
use WFBP\Payments\PaymentService;
use WFBP\REST\Routes;

final class Plugin
{
    private static ?self $instance = null;

    public static function instance(): self
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function boot(): void
    {
        $settings = new Settings();
        $currencyService = new CurrencyService($settings);
        $duffel = new DuffelClient($settings);
        $booking = new BookingService($duffel, $currencyService, $settings);
        $payments = new PaymentService($settings, $currencyService);

        (new SettingsPage($settings))->register();
        (new Routes($booking, $payments, $duffel, $settings))->register();
        (new Assets($settings, $currencyService))->register();
        (new Shortcode())->register();
        (new WidgetRegistrar())->register();
    }
}
