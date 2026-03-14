<?php

declare(strict_types=1);

namespace App\Behavior;

use App\Domain\Customer;
use App\Domain\Order;
use App\Domain\OrderItem;

class AcceptOrder
{
    /**
     * @param OrderItem[] $items
     */
    public function __invoke(array $items, Customer $customer): Order
    {
        return new Order($customer, $items);
    }
}
