<?php

declare(strict_types=1);

namespace App\Domain;

final readonly class Address
{
    public function __construct(
        public string $countryCode,
        public string $postalCode,
        public string $city,
        public string $streetAddress,
    ) {}
}
