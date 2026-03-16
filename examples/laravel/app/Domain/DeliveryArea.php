<?php

declare(strict_types=1);

namespace App\Domain;

enum DeliveryArea: string
{
    case DomesticOnly = 'domestic_only';
    case International = 'international';
}
