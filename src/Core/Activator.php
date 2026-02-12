<?php

declare(strict_types=1);

namespace WFBP\Core;

use WFBP\Repository\Schema;

final class Activator
{
    public static function activate(): void
    {
        (new Schema())->createTables();
        add_option('wfbp_version', WFBP_VERSION, '', false);

        if (! get_option(Settings::OPTION_KEY)) {
            $settings = new Settings();
            $settings->update($settings->all());
        }

        flush_rewrite_rules();
    }
}
