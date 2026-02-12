<?php

declare(strict_types=1);

namespace WFBP\Booking;

use WP_Error;
use WFBP\API\DuffelClient;
use WFBP\Core\Settings;
use WFBP\Currency\CurrencyService;
use WFBP\Repository\OfferRequestRepository;
use WFBP\Repository\OrderRepository;

final class BookingService
{
    private DuffelClient $duffel;
    private CurrencyService $currency;
    private Settings $settings;
    private OfferRequestRepository $offers;
    private OrderRepository $orders;
    private AirportService $airports;

    public function __construct(DuffelClient $duffel, CurrencyService $currency, Settings $settings)
    {
        $this->duffel = $duffel;
        $this->currency = $currency;
        $this->settings = $settings;
        $this->offers = new OfferRequestRepository();
        $this->orders = new OrderRepository();
        $this->airports = new AirportService($duffel);
    }

    public function searchOffers(array $payload): array|WP_Error
    {
        $payload['data']['cabin_class'] = $payload['data']['cabin_class'] ?? $this->settings->get('default_cabin_class', 'economy');
        $offerRequest = $this->duffel->createOfferRequest($payload);

        if (is_wp_error($offerRequest)) {
            return $offerRequest;
        }

        $offerRequestId = (string) ($offerRequest['data']['id'] ?? '');
        $offers = [];

        if ($offerRequestId !== '') {
            for ($attempt = 0; $attempt < 4; $attempt++) {
                $offersResponse = $this->duffel->getOffers($offerRequestId);
                if (! is_wp_error($offersResponse) && ! empty($offersResponse['data']) && is_array($offersResponse['data'])) {
                    $offers = $offersResponse['data'];
                    break;
                }

                usleep(250000);
            }
        }

        $normalized = [
            'offer_request' => $offerRequest,
            'offers' => $offers,
            'meta' => [
                'offer_request_id' => $offerRequestId,
                'offers_count' => count($offers),
            ],
        ];

        $this->offers->insert($payload, $normalized);
        return $normalized;
    }

    public function searchAirports(string $keyword): array|WP_Error
    {
        return $this->airports->search($keyword);
    }

    public function createOrder(array $payload): array|WP_Error
    {
        $response = $this->duffel->createOrder($payload);
        if (is_wp_error($response)) {
            return $response;
        }

        $duffelId = (string) ($response['data']['id'] ?? '');
        $totalEur = (float) ($response['data']['total_amount'] ?? 0);
        $localOrderId = $this->orders->insert($duffelId, 'created', 'pending', $totalEur);

        $response['meta']['local_order_id'] = $localOrderId;
        return $response;
    }

    public function priceForCurrency(float $amountEur, string $currency): array
    {
        $converted = $this->currency->convertFromEur($amountEur, $currency);

        return [
            'converted_amount' => $converted,
            'converted_formatted' => $this->currency->format($converted, $currency),
            'eur_reference' => $this->currency->format($amountEur, 'EUR'),
        ];
    }
}
