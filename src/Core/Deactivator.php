<?php

declare(strict_types=1);

namespace WFBP\Core;

final class Deactivator
{
    public static function deactivate(): void
    {
        flush_rewrite_rules();
    }
}
