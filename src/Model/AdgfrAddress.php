<?php

declare(strict_types=1);

namespace Geocoder\Provider\Adgfr\Model;

use Geocoder\Model\Address;
use Geocoder\Provider\Adgfr\Enum\Type;

final class AdgfrAddress extends Address
{
    private Type $type;
    private string $id;
    private string $cityCode;
    private ?string $district;
    private ?int $population;

    public function getId(): string
    {
        return $this->id;
    }

    public function withId(string $id): self
    {
        $new = clone $this;
        $new->id = $id;

        return $new;
    }

    public function getType(): Type
    {
        return $this->type;
    }

    public function withType(string $type): self
    {
        $new = clone $this;
        $new->type = Type::from($type);

        return $new;
    }

    public function getCityCode(): string
    {
        return $this->cityCode;
    }

    public function withCityCode(string $cityCode): self
    {
        $new = clone $this;
        $new->cityCode = $cityCode;

        return $new;
    }

    public function getDistrict(): ?string
    {
        return $this->district;
    }

    public function withDistrict(?string $district): self
    {
        $new = clone $this;
        $new->district = $district;

        return $new;
    }

    public function getPopulation(): ?int
    {
        return $this->population;
    }

    public function withPopulation(?int $population): self
    {
        $new = clone $this;
        $new->population = $population;

        return $new;
    }
}
