<?php

declare(strict_types=1);

namespace Geocoder\Provider\Adgfr\Enum;

enum Type: string
{
    case HouseNumber = 'housenumber';
    case Street = 'street';
    case Locality = 'locality';
    case Municipality = 'municipality';
}
