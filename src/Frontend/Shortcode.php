<?php

declare(strict_types=1);

namespace WFBP\Frontend;

final class Shortcode
{
    public function register(): void
    {
        add_shortcode('wfbp_search', [$this, 'render']);
    }

    public function render(): string
    {
        ob_start();
        include WFBP_PATH . 'templates/search-form.php';
        return (string) ob_get_clean();
    }
}
