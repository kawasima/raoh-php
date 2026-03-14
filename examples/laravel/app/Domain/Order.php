<?php

declare(strict_types=1);

namespace App\Domain;

final readonly class Order
{
    /**
     * @param OrderItem[] $items
     */
    public function __construct(
        public Customer $customer,
        public array    $items,
    ) {}
}
