<?php

declare(strict_types=1);

namespace App\Behavior;

use App\Domain\DeliveryAvailability;
use App\Domain\DeliveryProvider;
use App\Domain\DeliveryRequest;
use Raoh\Path;
use Raoh\Result;

class SelectDeliveryProvider
{
    /** @return Result<DeliveryProvider> */
    public function __invoke(DeliveryRequest $deliveryRequest, DeliveryProvider $provider): Result
    {
        $dow = (int) $deliveryRequest->desiredDeliveryDate->format('N'); // 1=Mon … 7=Sun

        if ($provider->deliveryAvailability === DeliveryAvailability::WeekdaysOnly && $dow >= 6) {
            return Result::fail(
                Path::of('desiredDeliveryDate'),
                'weekend_delivery_unavailable',
                'The selected delivery provider does not deliver on weekends.',
            );
        }

        return Result::ok($provider);
    }
}
