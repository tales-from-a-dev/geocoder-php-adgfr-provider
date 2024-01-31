<?php

declare(strict_types=1);

/*
 * This file is part of the Geocoder package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Geocoder\Provider\Nominatim\Tests;

use Geocoder\IntegrationTest\ProviderIntegrationTest;
use Geocoder\Provider\Adgfr\Adgfr;
use Psr\Http\Client\ClientInterface;

final class IntegrationTest extends ProviderIntegrationTest
{
    protected bool $testAddress = true;

    protected bool $testReverse = true;

    protected bool $testIpv4 = false;

    protected bool $testIpv6 = false;

    protected array $skippedTests = [
        'testGeocodeQuery' => 'This provider supports France only.',
        'testReverseQuery' => 'This provider supports France only.',
    ];

    protected function createProvider(ClientInterface $httpClient): Adgfr
    {
        return new Adgfr($httpClient);
    }

    protected function getCacheDir(): string
    {
        return __DIR__.'/.cached_responses';
    }

    protected function getApiKey(): string
    {
        return '';
    }
}
