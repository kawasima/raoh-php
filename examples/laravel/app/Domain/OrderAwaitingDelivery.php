<?php

declare(strict_types=1);

namespace App\Domain;

final readonly class OrderAwaitingDelivery
{
    public function __construct(
        public Order           $order,
        public DeliveryRequest $deliveryRequest,
    ) {}
}
