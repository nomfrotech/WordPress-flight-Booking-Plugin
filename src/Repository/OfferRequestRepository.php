<?php

declare(strict_types=1);

namespace WFBP\Repository;

final class OfferRequestRepository
{
    public function insert(array $payload, array $response): int
    {
        global $wpdb;
        $table = $wpdb->prefix . 'wfbp_offer_requests';
        $wpdb->insert(
            $table,
            [
                'payload' => wp_json_encode($payload),
                'response' => wp_json_encode($response),
                'created_at' => current_time('mysql', true),
            ],
            ['%s', '%s', '%s']
        );
        return (int) $wpdb->insert_id;
    }
}
