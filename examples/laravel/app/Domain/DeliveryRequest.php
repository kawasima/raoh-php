<?php

declare(strict_types=1);

namespace App\Domain;

use DateTimeImmutable;

final readonly class DeliveryRequest
{
    public function __construct(
        public DateTimeImmutable $desiredDeliveryDate,
        public Address           $deliveryAddress,
    ) {}
}
