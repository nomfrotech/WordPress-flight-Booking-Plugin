<?php

declare(strict_types=1);

namespace WFBP\Booking;

use WP_Error;
use WFBP\API\DuffelClient;

final class AirportService
{
    private DuffelClient $duffel;

    /** @var array<int,array<string,string>> */
    private array $fallbackAirports;

    public function __construct(DuffelClient $duffel)
    {
        $this->duffel = $duffel;
        $this->fallbackAirports = [
            ['iata_code' => 'LOS', 'name' => 'Murtala Muhammed International Airport', 'city_name' => 'Lagos', 'country_name' => 'Nigeria'],
            ['iata_code' => 'ABV', 'name' => 'Nnamdi Azikiwe International Airport', 'city_name' => 'Abuja', 'country_name' => 'Nigeria'],
            ['iata_code' => 'LHR', 'name' => 'Heathrow Airport', 'city_name' => 'London', 'country_name' => 'United Kingdom'],
            ['iata_code' => 'LGW', 'name' => 'Gatwick Airport', 'city_name' => 'London', 'country_name' => 'United Kingdom'],
            ['iata_code' => 'DXB', 'name' => 'Dubai International Airport', 'city_name' => 'Dubai', 'country_name' => 'UAE'],
            ['iata_code' => 'JFK', 'name' => 'John F. Kennedy International Airport', 'city_name' => 'New York', 'country_name' => 'USA'],
            ['iata_code' => 'YYZ', 'name' => 'Toronto Pearson International Airport', 'city_name' => 'Toronto', 'country_name' => 'Canada'],
            ['iata_code' => 'NBO', 'name' => 'Jomo Kenyatta International Airport', 'city_name' => 'Nairobi', 'country_name' => 'Kenya'],
            ['iata_code' => 'KGL', 'name' => 'Kigali International Airport', 'city_name' => 'Kigali', 'country_name' => 'Rwanda'],
            ['iata_code' => 'ACC', 'name' => 'Kotoka International Airport', 'city_name' => 'Accra', 'country_name' => 'Ghana'],
            ['iata_code' => 'CDG', 'name' => 'Charles de Gaulle Airport', 'city_name' => 'Paris', 'country_name' => 'France'],
            ['iata_code' => 'FRA', 'name' => 'Frankfurt Airport', 'city_name' => 'Frankfurt', 'country_name' => 'Germany'],
        ];
    }

    public function search(string $keyword): array|WP_Error
    {
        $keyword = trim(sanitize_text_field($keyword));
        if (strlen($keyword) < 2) {
            return ['data' => []];
        }

        $duffel = $this->duffel->searchAirports($keyword);
        if (! is_wp_error($duffel) && ! empty($duffel['data']) && is_array($duffel['data'])) {
            return ['data' => $this->normalize($duffel['data'])];
        }

        return ['data' => $this->fallbackLookup($keyword)];
    }

    /**
     * @param array<int,mixed> $airports
     * @return array<int,array<string,string>>
     */
    private function normalize(array $airports): array
    {
        $normalized = [];
        foreach ($airports as $airport) {
            if (! is_array($airport)) {
                continue;
            }
            $normalized[] = [
                'id' => (string) ($airport['id'] ?? ''),
                'iata_code' => (string) ($airport['iata_code'] ?? ''),
                'name' => (string) ($airport['name'] ?? ''),
                'city_name' => (string) ($airport['city_name'] ?? ''),
                'country_name' => (string) ($airport['country_name'] ?? ''),
            ];
        }

        return array_values(array_filter($normalized, static fn (array $row): bool => $row['iata_code'] !== '' || $row['name'] !== ''));
    }

    /**
     * @return array<int,array<string,string>>
     */
    private function fallbackLookup(string $keyword): array
    {
        $keyword = mb_strtolower($keyword);

        $results = array_filter(
            $this->fallbackAirports,
            static function (array $airport) use ($keyword): bool {
                $blob = mb_strtolower(implode(' ', $airport));
                return str_contains($blob, $keyword);
            }
        );

        return array_slice(array_values($results), 0, 8);
    }
}
