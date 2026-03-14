<?php

declare(strict_types=1);

namespace App\Gateway;

use App\Domain\DeliveryAvailability;
use App\Domain\DeliveryProvider;
use App\Domain\DeliveryRequest;

class StubDeliveryProviderGateway implements DeliveryProviderGateway
{
    public function selectProvider(DeliveryRequest $deliveryRequest): DeliveryProvider
    {
        // Simulate: select a weekday-only provider for domestic, all-days for international
        $isInternational = $deliveryRequest->deliveryAddress->countryCode !== 'JP';

        return $isInternational
            ? new DeliveryProvider('GlobalEx', DeliveryAvailability::AllDays)
            : new DeliveryProvider('YamatoLogistics', DeliveryAvailability::WeekdaysOnly);
    }
}
