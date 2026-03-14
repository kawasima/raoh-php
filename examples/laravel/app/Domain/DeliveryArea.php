<?php

declare(strict_types=1);

namespace App\Domain;

enum DeliveryArea
{
    case DomesticOnly;
    case International;
}
