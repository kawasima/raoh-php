<?php

declare(strict_types=1);

namespace Raoh\Tests\Fixtures;

enum Status: string
{
    case Active   = 'active';
    case Inactive = 'inactive';
}
