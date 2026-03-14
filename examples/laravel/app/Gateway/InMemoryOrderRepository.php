<?php

declare(strict_types=1);

namespace App\Gateway;

use App\Domain\Order;
use App\Domain\OrderAwaitingDelivery;
use App\Domain\ProductionOrder;
use Illuminate\Support\Str;

class InMemoryOrderRepository implements OrderRepository
{
    /** @var array<string, Order> persisted across requests via the cache */
    private array $orders = [];

    public function __construct()
    {
        $this->orders = cache()->get('_orders', []);
    }

    public function save(Order|OrderAwaitingDelivery|ProductionOrder $order): string
    {
        $id = (string) Str::uuid();

        $this->orders[$id] = match (true) {
            $order instanceof OrderAwaitingDelivery => $order->order,
            $order instanceof ProductionOrder       => $order->order,
            default                                 => $order,
        };

        cache()->put('_orders', $this->orders, now()->addHours(1));

        return $id;
    }

    public function findById(string $orderId): ?Order
    {
        return $this->orders[$orderId] ?? null;
    }
}
