<?php

declare(strict_types=1);

namespace Geocoder\Provider\Adgfr\Tests;

use Geocoder\Exception\InvalidArgument;
use Geocoder\Exception\UnsupportedOperation;
use Geocoder\IntegrationTest\BaseTestCase;
use Geocoder\Model\Address;
use Geocoder\Model\AddressCollection;
use Geocoder\Provider\Adgfr\Adgfr;
use Geocoder\Provider\Adgfr\Enum\Type;
use Geocoder\Provider\Adgfr\Model\AdgfrAddress;
use Geocoder\Query\GeocodeQuery;
use Geocoder\Query\ReverseQuery;

final class AdgfrTest extends BaseTestCase
{
    protected function getCacheDir(): ?string
    {
        return null;
    }

    public function testGetName(): void
    {
        $provider = new Adgfr($this->getHttpClient());

        $this->assertSame('adgfr', $provider->getName());
    }

    public function testGeocodeWithRealAddress(): void
    {
        $provider = new Adgfr($this->getHttpClient());
        $results = $provider->geocodeQuery(GeocodeQuery::create('20 avenue de ségur'));

        $this->assertInstanceOf(AddressCollection::class, $results);
        $this->assertCount(5, $results);

        /** @var AdgfrAddress $address */
        $address = $results->first();
        $this->assertInstanceOf(Address::class, $address);
        $this->assertEqualsWithDelta(48.850699, $address->getCoordinates()->getLatitude(), 0.00001);
        $this->assertEqualsWithDelta(2.308628, $address->getCoordinates()->getLongitude(), 0.00001);
        $this->assertSame('20', $address->getStreetNumber());
        $this->assertSame('Avenue de Ségur', $address->getStreetName());
        $this->assertSame('75007', $address->getPostalCode());
        $this->assertSame('Paris', $address->getLocality());
        $this->assertSame('FR', $address->getCountry()->getCode());
        $this->assertSame('75107_8909_00020', $address->getId());
        $this->assertSame(Type::HouseNumber, $address->getType());
        $this->assertSame('75107', $address->getCityCode());
        $this->assertSame('Paris 7e Arrondissement', $address->getDistrict());
    }

    public function testGeocodeHouseNumberTypeQuery(): void
    {
        $provider = new Adgfr($this->getHttpClient());
        $results = $provider->geocodeQuery(
            GeocodeQuery::create('20 avenue Kléber, Paris')->withData('type', Type::HouseNumber->value)
        );

        $this->assertInstanceOf(AddressCollection::class, $results);

        /** @var AdgfrAddress $address */
        $address = $results->first();
        $this->assertInstanceOf(Address::class, $address);
        $this->assertSame('20', $address->getStreetNumber());
        $this->assertSame('Avenue Kléber', $address->getStreetName());
        $this->assertSame('75016', $address->getPostalCode());
        $this->assertSame('Paris', $address->getLocality());
    }

    public function testGeocodeStreetTypeQuery(): void
    {
        $provider = new Adgfr($this->getHttpClient());
        $results = $provider->geocodeQuery(
            GeocodeQuery::create('20 avenue Kléber, Paris')->withData('type', Type::Street->value)
        );

        $this->assertInstanceOf(AddressCollection::class, $results);

        /** @var AdgfrAddress $address */
        $address = $results->first();
        $this->assertInstanceOf(Address::class, $address);
        $this->assertNull($address->getStreetNumber());
        $this->assertSame('Avenue Kléber', $address->getStreetName());
        $this->assertSame('75016', $address->getPostalCode());
        $this->assertSame('Paris', $address->getLocality());
    }

    public function testGeocodeLocalityQuery(): void
    {
        $provider = new Adgfr($this->getHttpClient());
        $results = $provider->geocodeQuery(
            GeocodeQuery::create('20 avenue Kléber, Paris')->withData('type', Type::Locality->value)
        );

        $this->assertInstanceOf(AddressCollection::class, $results);

        /** @var AdgfrAddress $address */
        $address = $results->first();
        $this->assertInstanceOf(Address::class, $address);
        $this->assertNull($address->getStreetNumber());
        $this->assertNull($address->getStreetName());
        $this->assertEqualsWithDelta(48.994028, $address->getCoordinates()->getLatitude(), 0.00001);
        $this->assertEqualsWithDelta(2.509919, $address->getCoordinates()->getLongitude(), 0.00001);
        $this->assertSame('95700', $address->getPostalCode());
        $this->assertSame('Roissy-en-France', $address->getLocality());
    }

    public function testReverseWithCoordinates(): void
    {
        $provider = new Adgfr($this->getHttpClient());
        $results = $provider->reverseQuery(ReverseQuery::fromCoordinates(48.850699, 2.308628));

        $this->assertInstanceOf(AddressCollection::class, $results);
        $this->assertCount(5, $results);

        /** @var AdgfrAddress $result */
        $address = $results->first();
        $this->assertInstanceOf(Address::class, $address);
        $this->assertEqualsWithDelta(48.850699, $address->getCoordinates()->getLatitude(), 0.00001);
        $this->assertEqualsWithDelta(2.308628, $address->getCoordinates()->getLongitude(), 0.00001);
        $this->assertSame('20', $address->getStreetNumber());
        $this->assertSame('Avenue de Ségur', $address->getStreetName());
        $this->assertSame('75007', $address->getPostalCode());
        $this->assertSame('Paris', $address->getLocality());
        $this->assertSame('FR', $address->getCountry()->getCode());
    }

    public function testGeocodeWithLocalhostIPv4(): void
    {
        $this->expectException(UnsupportedOperation::class);
        $this->expectExceptionMessage('The Adgfr provider does not support IP addresses.');

        $provider = new Adgfr($this->getMockedHttpClient());
        $provider->geocodeQuery(GeocodeQuery::create('127.0.0.1'));
    }

    public function testGeocodeWithRealIPv6(): void
    {
        $this->expectException(UnsupportedOperation::class);
        $this->expectExceptionMessage('The Adgfr provider does not support IP addresses.');

        $provider = new Adgfr($this->getMockedHttpClient());
        $provider->geocodeQuery(GeocodeQuery::create('::ffff:88.188.221.14'));
    }

    public function testGeocodeWithEmptyQuery(): void
    {
        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage('Geocode query cannot be empty');

        $provider = new Adgfr($this->getMockedHttpClient());
        $provider->geocodeQuery(GeocodeQuery::create(''));
    }
}
