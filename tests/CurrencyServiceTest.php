<?php

declare(strict_types=1);

namespace WFBP\Tests;

use PHPUnit\Framework\TestCase;
use WFBP\Core\Settings;
use WFBP\Currency\CurrencyService;

final class CurrencyServiceTest extends TestCase
{
    public function testConvertFromEur(): void
    {
        $settings = $this->createMock(Settings::class);
        $settings->method('get')->willReturn('{"USD":2,"EUR":1}');

        $service = new CurrencyService($settings);
        self::assertSame(20.0, $service->convertFromEur(10.0, 'USD'));
    }
}
