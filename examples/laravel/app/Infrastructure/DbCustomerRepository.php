<?php

declare(strict_types=1);

namespace App\Infrastructure;

use App\Domain\Customer;
use App\Gateway\CustomerRepository;
use Illuminate\Support\Facades\DB;

final class DbCustomerRepository implements CustomerRepository
{
    public function findById(string $customerId): ?Customer
    {
        $row = DB::table('customers')->where('id', $customerId)->first();
        if ($row === null) {
            return null;
        }

        return RowDecoders::customer()->decode((array) $row)->getOrThrow();
    }
}
