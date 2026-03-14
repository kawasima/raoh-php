<?php

declare(strict_types=1);

namespace App\Gateway;

use App\Domain\DeliveryArea;
use App\Domain\MadeToOrderProduct;
use App\Domain\Product;
use App\Domain\StandardProduct;
use App\Domain\Supplier;

class InMemoryProductRepository implements ProductRepository
{
    /** @var array<string, Product> */
    private array $products;

    public function __construct()
    {
        $this->products = [
            'PROD-001' => new StandardProduct(
                productId:    'PROD-001',
                name:         'Widget A',
                price:        1000,
                deliveryArea: DeliveryArea::DomesticOnly,
            ),
            'PROD-002' => new StandardProduct(
                productId:    'PROD-002',
                name:         'Gadget B',
                price:        2500,
                deliveryArea: DeliveryArea::International,
            ),
            'PROD-003' => new MadeToOrderProduct(
                productId:    'PROD-003',
                name:         'Custom Frame C',
                price:        15000,
                deliveryArea: DeliveryArea::DomesticOnly,
                supplier:     new Supplier('SUP-001', 'Acme Manufacturing'),
            ),
        ];
    }

    public function findById(string $productId): ?Product
    {
        return $this->products[$productId] ?? null;
    }
}
