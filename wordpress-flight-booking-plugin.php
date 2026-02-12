<?php
/**
 * Plugin Name: WordPress Flight Booking Plugin
 * Plugin URI: https://nomfro.com
 * Description: Commercial-grade flight booking engine with Duffel API, multi-currency checkout, payment provider handoff, REST APIs, shortcode UI, and Elementor support.
 * Version: 1.0.0
 * Author: Nomfro Technologies
 * Author URI: https://nomfro.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wfbp
 * Domain Path: /languages
 *
 * @package WFBP
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

defined('WFBP_VERSION') || define('WFBP_VERSION', '1.0.0');
defined('WFBP_FILE') || define('WFBP_FILE', __FILE__);
defined('WFBP_PATH') || define('WFBP_PATH', __DIR__ . '/');
defined('WFBP_URL') || define('WFBP_URL', plugin_dir_url(__FILE__));
defined('WFBP_MIN_PHP') || define('WFBP_MIN_PHP', '8.0');
defined('WFBP_MIN_WP') || define('WFBP_MIN_WP', '6.0');

if (! function_exists('wfbp_load_autoloader')) {
    function wfbp_load_autoloader(): bool
    {
        $autoloader = WFBP_PATH . 'vendor/autoload.php';
        if (is_readable($autoloader)) {
            require_once $autoloader;
            return true;
        }

        spl_autoload_register(
            static function (string $class): void {
                if (strpos($class, 'WFBP\\') !== 0) {
                    return;
                }
                $relative = str_replace('WFBP\\', '', $class);
                $relative = str_replace('\\', DIRECTORY_SEPARATOR, $relative);
                $file = WFBP_PATH . 'src/' . $relative . '.php';
                if (is_readable($file)) {
                    require_once $file;
                }
            }
        );

        return true;
    }
}

if (! function_exists('wfbp_dependency_check')) {
    function wfbp_dependency_check(): bool
    {
        global $wp_version;

        if (version_compare(PHP_VERSION, WFBP_MIN_PHP, '<')) {
            add_action(
                'admin_notices',
                static function (): void {
                    echo '<div class="notice notice-error"><p>' . esc_html__('WordPress Flight Booking Plugin requires PHP 8.0 or greater.', 'wfbp') . '</p></div>';
                }
            );
            return false;
        }

        if (version_compare((string) $wp_version, WFBP_MIN_WP, '<')) {
            add_action(
                'admin_notices',
                static function (): void {
                    echo '<div class="notice notice-error"><p>' . esc_html__('WordPress Flight Booking Plugin requires WordPress 6.0 or greater.', 'wfbp') . '</p></div>';
                }
            );
            return false;
        }

        return true;
    }
}

wfbp_load_autoloader();

register_activation_hook(
    __FILE__,
    static function (): void {
        if (! wfbp_dependency_check()) {
            deactivate_plugins(plugin_basename(__FILE__));
            wp_die(esc_html__('Plugin dependencies are not met.', 'wfbp'));
        }

        WFBP\Core\Activator::activate();
    }
);

register_deactivation_hook(
    __FILE__,
    static function (): void {
        WFBP\Core\Deactivator::deactivate();
    }
);

if (wfbp_dependency_check()) {
    add_action(
        'plugins_loaded',
        static function (): void {
            load_plugin_textdomain('wfbp', false, dirname(plugin_basename(__FILE__)) . '/languages');
            WFBP\Core\Plugin::instance()->boot();
        }
    );
}
