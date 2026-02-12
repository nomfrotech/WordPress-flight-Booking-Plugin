<?php

declare(strict_types=1);

if (! defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

delete_option('wfbp_settings');
delete_option('wfbp_version');
delete_transient('wfbp_currency_rates');

global $wpdb;
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}wfbp_offer_requests");
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}wfbp_orders");
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}wfbp_transactions");
