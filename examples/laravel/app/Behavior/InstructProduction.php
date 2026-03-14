<?php

declare(strict_types=1);

namespace App\Behavior;

use App\Domain\MadeToOrderProduct;
use App\Domain\Order;
use App\Domain\ProductionOrder;
use DateTimeImmutable;
use Raoh\Path;
use Raoh\Result;

class InstructProduction
{
    /** @return Result<ProductionOrder> */
    public function __invoke(Order $order, DateTimeImmutable $scheduledDeliveryDate): Result
    {
        $hasMadeToOrder = false;
        foreach ($order->items as $item) {
            if ($item->product instanceof MadeToOrderProduct) {
                $hasMadeToOrder = true;
                break;
            }
        }

        if (!$hasMadeToOrder) {
            return Result::fail(
                Path::root(),
                'no_made_to_order_items',
                'Order contains no made-to-order products.',
            );
        }

        return Result::ok(new ProductionOrder($order, $scheduledDeliveryDate));
    }
}
