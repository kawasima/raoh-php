<?php

declare(strict_types=1);

namespace App\Domain;

use DateTimeImmutable;

final readonly class ProductionOrder
{
    public function __construct(
        public Order             $order,
        public DateTimeImmutable $scheduledDeliveryDate,
    ) {}
}
