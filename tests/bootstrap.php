<?php

declare(strict_types=1);

if (! class_exists('WP_Error')) {
    class WP_Error
    {
        public function __construct(private string $code = '', private string $message = '', private array $data = [])
        {
        }

        public function get_error_message(): string
        {
            return $this->message;
        }
    }
}

if (! function_exists('__')) {
    function __($text, $domain = null)
    {
        return $text;
    }
}
if (! function_exists('trailingslashit')) {
    function trailingslashit($value)
    {
        return rtrim((string) $value, '/') . '/';
    }
}
if (! function_exists('get_transient')) {
    function get_transient($key)
    {
        return false;
    }
}
if (! function_exists('set_transient')) {
    function set_transient($key, $value, $ttl)
    {
        return true;
    }
}
if (! function_exists('number_format_i18n')) {
    function number_format_i18n($number, $decimals = 0)
    {
        return number_format((float) $number, (int) $decimals, '.', ',');
    }
}

require_once dirname(__DIR__) . '/src/Core/Settings.php';
require_once dirname(__DIR__) . '/src/Currency/CurrencyService.php';
require_once dirname(__DIR__) . '/src/API/DuffelClient.php';
