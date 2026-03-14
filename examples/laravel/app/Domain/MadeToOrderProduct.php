<?php

declare(strict_types=1);

namespace App\Domain;

final readonly class MadeToOrderProduct extends Product
{
    public function __construct(
        string       $productId,
        string       $name,
        int          $price,
        DeliveryArea $deliveryArea,
        public Supplier $supplier,
    ) {
        parent::__construct($productId, $name, $price, $deliveryArea);
    }
}
