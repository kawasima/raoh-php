<?php

declare(strict_types=1);

namespace App\Domain;

final readonly class Customer
{
    public function __construct(
        public string $customerId,
        public string $name,
        public string $email,
    ) {}
}
