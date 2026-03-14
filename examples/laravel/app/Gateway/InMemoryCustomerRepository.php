<?php

declare(strict_types=1);

namespace App\Gateway;

use App\Domain\Customer;

class InMemoryCustomerRepository implements CustomerRepository
{
    /** @var array<string, Customer> */
    private array $customers;

    public function __construct()
    {
        $this->customers = [
            'CUST-001' => new Customer('CUST-001', 'Alice Tanaka',  'alice@example.com'),
            'CUST-002' => new Customer('CUST-002', 'Bob Yamamoto',  'bob@example.com'),
            'CUST-003' => new Customer('CUST-003', 'Carol Suzuki',  'carol@example.com'),
        ];
    }

    public function findById(string $customerId): ?Customer
    {
        return $this->customers[$customerId] ?? null;
    }
}
