<?php

declare(strict_types=1);

namespace App\Behavior;

use App\Domain\ClassifiedOrderItems;
use App\Domain\MadeToOrderProduct;
use App\Domain\OrderItem;

class ClassifyOrderItems
{
    /**
     * @param OrderItem[] $items
     */
    public function __invoke(array $items): ClassifiedOrderItems
    {
        $standard    = [];
        $madeToOrder = [];

        foreach ($items as $item) {
            if ($item->product instanceof MadeToOrderProduct) {
                $madeToOrder[] = $item;
            } else {
                $standard[] = $item;
            }
        }

        return new ClassifiedOrderItems($standard, $madeToOrder);
    }
}
