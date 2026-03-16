<?php

declare(strict_types=1);

namespace App\Domain;

enum DeliveryAvailability: string
{
    case WeekdaysOnly = 'weekdays_only';
    case AllDays = 'all_days';
}
