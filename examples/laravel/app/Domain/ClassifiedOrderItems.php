<?php

declare(strict_types=1);

namespace App\Domain;

final readonly class ClassifiedOrderItems
{
    /**
     * @param OrderItem[] $standardItems
     * @param OrderItem[] $madeToOrderItems
     */
    public function __construct(
        public array $standardItems,
        public array $madeToOrderItems,
    ) {}

    public function hasStandardItems(): bool
    {
        return count($this->standardItems) > 0;
    }

    public function hasMadeToOrderItems(): bool
    {
        return count($this->madeToOrderItems) > 0;
    }
}
