<?php

declare(strict_types=1);

namespace App\Domain;

final readonly class OrderItem
{
    public function __construct(
        public Product $product,
        public int     $quantity,
    ) {}
}
