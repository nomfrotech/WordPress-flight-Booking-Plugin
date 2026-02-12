<?php

declare(strict_types=1);

namespace WFBP\Repository;

final class OrderRepository
{
    public function insert(string $duffelOrderId, string $status, string $paymentStatus, float $totalEur): int
    {
        global $wpdb;
        $table = $wpdb->prefix . 'wfbp_orders';
        $wpdb->insert(
            $table,
            [
                'duffel_order_id' => $duffelOrderId,
                'status' => $status,
                'payment_status' => $paymentStatus,
                'total_eur' => $totalEur,
                'created_at' => current_time('mysql', true),
            ],
            ['%s', '%s', '%s', '%f', '%s']
        );
        return (int) $wpdb->insert_id;
    }

    public function updatePaymentStatusByDuffelId(string $duffelOrderId, string $paymentStatus): bool
    {
        global $wpdb;
        $table = $wpdb->prefix . 'wfbp_orders';
        $updated = $wpdb->update($table, ['payment_status' => $paymentStatus], ['duffel_order_id' => $duffelOrderId], ['%s'], ['%s']);
        return $updated !== false;
    }
}
