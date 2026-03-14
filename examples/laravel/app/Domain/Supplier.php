<?php

declare(strict_types=1);

namespace App\Domain;

final readonly class Supplier
{
    public function __construct(
        public string $supplierId,
        public string $name,
    ) {}
}
