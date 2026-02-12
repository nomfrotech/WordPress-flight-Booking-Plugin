<?php

declare(strict_types=1);

namespace WFBP\Repository;

final class TransactionRepository
{
    public function insert(int $orderId, string $provider, string $currency, float $amount, string $status, string $reference): int
    {
        global $wpdb;
        $table = $wpdb->prefix . 'wfbp_transactions';
        $wpdb->insert(
            $table,
            [
                'order_id' => $orderId,
                'provider' => $provider,
                'currency' => $currency,
                'amount' => $amount,
                'status' => $status,
                'reference' => $reference,
                'created_at' => current_time('mysql', true),
            ],
            ['%d', '%s', '%s', '%f', '%s', '%s', '%s']
        );

        return (int) $wpdb->insert_id;
    }

    public function updateStatusByReference(string $reference, string $status): bool
    {
        global $wpdb;
        $table = $wpdb->prefix . 'wfbp_transactions';
        $updated = $wpdb->update($table, ['status' => $status], ['reference' => $reference], ['%s'], ['%s']);
        return $updated !== false;
    }
}
