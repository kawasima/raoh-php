<?php

declare(strict_types=1);

namespace App\Domain;

enum DeliveryAvailability
{
    case WeekdaysOnly;
    case AllDays;
}
