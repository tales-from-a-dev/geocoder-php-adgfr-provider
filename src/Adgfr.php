<?php

declare(strict_types=1);

namespace Geocoder\Provider\Adgfr;

use Geocoder\Collection;
use Geocoder\Exception\InvalidServerResponse;
use Geocoder\Exception\UnsupportedOperation;
use Geocoder\Http\Provider\AbstractHttpProvider;
use Geocoder\Location;
use Geocoder\Model\AddressBuilder;
use Geocoder\Model\AddressCollection;
use Geocoder\Provider\Adgfr\Enum\Type;
use Geocoder\Provider\Adgfr\Model\AdgfrAddress;
use Geocoder\Query\GeocodeQuery;
use Geocoder\Query\ReverseQuery;

final class Adgfr extends AbstractHttpProvider
{
    final public const PROVIDER_NAME = 'adgfr';
    final public const API_URL = 'https://api-adresse.data.gouv.fr';

    public function geocodeQuery(GeocodeQuery $query): Collection
    {
        // This API doesn't handle IPs
        if (filter_var($query->getText(), \FILTER_VALIDATE_IP)) {
            throw new UnsupportedOperation('The Adgfr provider does not support IP addresses.');
        }

        $url = sprintf(
            '%s/search/?%s',
            self::API_URL,
            http_build_query([
                'q' => $query->getText(),
                'limit' => $query->getLimit(),
                'lat' => $query->getData('lat'),
                'lon' => $query->getData('lon'),
                'postcode' => $query->getData('postcode'),
                'citycode' => $query->getData('citycode'),
                'type' => Type::tryFrom($query->getData('type', ''))?->value,
            ], '', '&', \PHP_QUERY_RFC3986)
        );

        return $this->executeQuery($url);
    }

    public function reverseQuery(ReverseQuery $query): Collection
    {
        $coordinates = $query->getCoordinates();

        $url = sprintf(
            '%s/reverse/?%s',
            self::API_URL,
            http_build_query([
                'lat' => $coordinates->getLatitude(),
                'lon' => $coordinates->getLongitude(),
            ], '', '&', \PHP_QUERY_RFC3986)
        );

        return $this->executeQuery($url);
    }

    public function getName(): string
    {
        return self::PROVIDER_NAME;
    }

    private function executeQuery(string $url): AddressCollection
    {
        $content = $this->getParsedResponse($this->getRequest($url));

        $json = json_decode($content, false, 512, \JSON_THROW_ON_ERROR);

        if (!$json instanceof \stdClass) {
            throw InvalidServerResponse::create($url);
        }

        $results = [];
        foreach ($json->features as $feature) {
            $results[] = $this->jsonResultToLocation($feature);
        }

        return new AddressCollection($results);
    }

    private function jsonResultToLocation(\stdClass $feature): Location
    {
        [$longitude, $latitude] = $feature->geometry->coordinates;

        $builder = new AddressBuilder($this->getName());
        $builder
            ->setCoordinates($latitude, $longitude)
            ->setStreetNumber($feature->properties->housenumber ?? null)
            ->setStreetName($feature->properties->street ?? null)
            ->setPostalCode($feature->properties->postcode ?? null)
            ->setLocality($feature->properties->city ?? null)
            ->setCountry('France')
            ->setCountryCode('FR')
        ;

        /** @var AdgfrAddress $location */
        $location = $builder->build(AdgfrAddress::class);
        $location = $location->withType($feature->properties->type);
        $location = $location->withId($feature->properties->id);
        $location = $location->withCityCode($feature->properties->citycode);
        $location = $location->withDistrict($feature->properties->district ?? null);
        $location = $location->withPopulation($feature->properties->population ?? null);

        return $location;
    }
}
