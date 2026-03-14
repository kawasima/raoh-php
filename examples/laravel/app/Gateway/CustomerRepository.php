<?php

declare(strict_types=1);

namespace App\Gateway;

use App\Domain\Customer;

interface CustomerRepository
{
    public function findById(string $customerId): ?Customer;
}
