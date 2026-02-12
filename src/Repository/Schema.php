<?php

declare(strict_types=1);

namespace WFBP\Repository;

final class Schema
{
    public function createTables(): void
    {
        global $wpdb;

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $charset = $wpdb->get_charset_collate();
        $offers = $wpdb->prefix . 'wfbp_offer_requests';
        $orders = $wpdb->prefix . 'wfbp_orders';
        $transactions = $wpdb->prefix . 'wfbp_transactions';

        $sql = "CREATE TABLE $offers (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            payload LONGTEXT NOT NULL,
            response LONGTEXT NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset;
        CREATE TABLE $orders (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            duffel_order_id VARCHAR(191) NOT NULL,
            status VARCHAR(50) NOT NULL,
            payment_status VARCHAR(50) NOT NULL,
            total_eur DECIMAL(12,2) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY duffel_order_id (duffel_order_id)
        ) $charset;
        CREATE TABLE $transactions (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            order_id BIGINT UNSIGNED NOT NULL,
            provider VARCHAR(50) NOT NULL,
            currency VARCHAR(10) NOT NULL,
            amount DECIMAL(12,2) NOT NULL,
            status VARCHAR(50) NOT NULL,
            reference VARCHAR(191) NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY order_id (order_id),
            KEY reference (reference)
        ) $charset;";

        dbDelta($sql);
    }
}
