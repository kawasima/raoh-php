<?php

declare(strict_types=1);

namespace App\Gateway;

use App\Domain\Product;

interface ProductRepository
{
    public function findById(string $productId): ?Product;
}
