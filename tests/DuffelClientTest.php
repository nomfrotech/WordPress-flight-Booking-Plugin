<?php

declare(strict_types=1);

namespace WFBP\Tests;

use PHPUnit\Framework\TestCase;
use WFBP\API\DuffelClient;
use WFBP\Core\Settings;

final class DuffelClientTest extends TestCase
{
    public function testMissingTokenReturnsError(): void
    {
        $settings = $this->createMock(Settings::class);
        $settings->method('get')->willReturnMap([
            ['duffel_environment', null, 'sandbox'],
            ['duffel_api_token', '', ''],
        ]);

        $client = new DuffelClient($settings);
        $response = $client->request('GET', '/air/orders');

        self::assertInstanceOf('WP_Error', $response);
    }
}
