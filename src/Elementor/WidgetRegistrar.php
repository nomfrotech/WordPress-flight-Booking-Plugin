<?php

declare(strict_types=1);

namespace WFBP\Elementor;

final class WidgetRegistrar
{
    public function register(): void
    {
        add_action('elementor/widgets/register', [$this, 'registerWidget']);
    }

    public function registerWidget($widgetsManager): void
    {
        if (! class_exists('Elementor\\Widget_Base')) {
            return;
        }

        require_once __DIR__ . '/FlightSearchWidget.php';
        $widgetsManager->register(new FlightSearchWidget());
    }
}
