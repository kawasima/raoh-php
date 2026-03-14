<?php

declare(strict_types=1);

namespace App\Domain;

/**
 * Sealed-like hierarchy: only StandardProduct and MadeToOrderProduct are valid.
 */
abstract readonly class Product
{
    public function __construct(
        public string       $productId,
        public string       $name,
        public int          $price,
        public DeliveryArea $deliveryArea,
    ) {}
}
