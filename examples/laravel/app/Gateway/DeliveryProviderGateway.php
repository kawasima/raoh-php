<?php

declare(strict_types=1);

namespace App\Gateway;

use App\Domain\DeliveryProvider;
use App\Domain\DeliveryRequest;

interface DeliveryProviderGateway
{
    public function selectProvider(DeliveryRequest $deliveryRequest): DeliveryProvider;
}
