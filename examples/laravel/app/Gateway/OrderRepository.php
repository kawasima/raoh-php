<?php

declare(strict_types=1);

namespace App\Gateway;

use App\Domain\Order;
use App\Domain\OrderAwaitingDelivery;
use App\Domain\ProductionOrder;

interface OrderRepository
{
    public function save(Order|OrderAwaitingDelivery|ProductionOrder $order): string;

    public function findById(string $orderId): ?Order;
}
