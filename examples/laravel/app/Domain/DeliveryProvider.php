<?php

declare(strict_types=1);

namespace App\Domain;

final readonly class DeliveryProvider
{
    public function __construct(
        public string               $name,
        public DeliveryAvailability $deliveryAvailability,
    ) {}
}
