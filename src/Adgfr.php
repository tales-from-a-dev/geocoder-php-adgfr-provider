<?php

declare(strict_types=1);

namespace Geocoder\Provider\Adgfr;

use Geocoder\Collection;
use Geocoder\Exception\InvalidArgument;
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
        $address = $query->getText();

        // This API doesn't handle IPs
        if (filter_var($address, \FILTER_VALIDATE_IP)) {
            throw new UnsupportedOperation('The Adgfr provider does not support IP addresses.');
        }

        $url = sprintf('%s/search/?q=%s', self::API_URL, urldecode($address));

        if ($limit = $query->getLimit()) {
            $url .= sprintf('&limit=%d', $limit);
        }

        if ($autocomplete = $query->getData('autocomplete')) {
            $url .= sprintf('&autocomplete=%d', $autocomplete);
        }

        if (
            ($lat = $query->getData('lat'))
            && ($lon = $query->getData('lon'))
        ) {
            $url .= sprintf('&lat=%F&lon=%F', $lat, $lon);
        }

        if ($type = $query->getData('type')) {
            if (\is_string($type)) {
                $type = Type::from($type);
            }

            if (!$type instanceof Type) {
                throw new InvalidArgument(sprintf('"type" must be a valid "%s" enum, "%s" given.', Type::class, get_debug_type($type)));
            }

            $url .= sprintf('&type=%s', $type->value);
        }

        if ($postcode = $query->getData('postcode')) {
            $url .= sprintf('&postcode=%s', $postcode);
        }

        if ($citycode = $query->getData('citycode')) {
            $url .= sprintf('&citycode=%s', $citycode);
        }

        $content = $this->executeQuery($url);

        $json = json_decode($content, false, 512, JSON_THROW_ON_ERROR);

        if (!is_object($json)) {
            throw InvalidServerResponse::create($url);
        }

        if (empty($json)) {
            return new AddressCollection([]);
        }

        $results = [];
        foreach ($json->features as $feature) {
            $results[] = $this->jsonResultToLocation($feature, false);
        }

        return new AddressCollection($results);
    }

    public function reverseQuery(ReverseQuery $query): Collection
    {
        return new AddressCollection([]);
    }

    public function getName(): string
    {
        return self::PROVIDER_NAME;
    }

    private function executeQuery(string $url): string
    {
        return $this->getParsedResponse($this->getRequest($url));
    }

    private function jsonResultToLocation(\stdClass $feature, bool $reverse): Location
    {
        [$latitude, $longitude] = $feature->geometry->coordinates;

        $builder = new AddressBuilder($this->getName());
        $builder
            ->setCoordinates($latitude, $longitude)
            ->setStreetNumber($feature->properties->housenumber ?? null)
            ->setStreetName($feature->properties->street ?? null)
            ->setPostalCode($feature->properties->postcode)
            ->setLocality($feature->properties->city)
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
