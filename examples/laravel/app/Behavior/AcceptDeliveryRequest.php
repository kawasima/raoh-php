<?php

declare(strict_types=1);

namespace App\Behavior;

use App\Domain\DeliveryArea;
use App\Domain\DeliveryRequest;
use App\Domain\Order;
use App\Domain\OrderAwaitingDelivery;
use Raoh\Path;
use Raoh\Result;

class AcceptDeliveryRequest
{
    /** @return Result<OrderAwaitingDelivery> */
    public function __invoke(Order $order, DeliveryRequest $deliveryRequest): Result
    {
        foreach ($order->items as $item) {
            if ($item->product->deliveryArea === DeliveryArea::DomesticOnly) {
                if ($deliveryRequest->deliveryAddress->countryCode !== 'JP') {
                    return Result::fail(
                        Path::of('country'),
                        'domestic_only',
                        'This product can only be delivered within Japan.',
                    );
                }
            }
        }

        return Result::ok(new OrderAwaitingDelivery($order, $deliveryRequest));
    }
}
