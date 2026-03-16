<?php

declare(strict_types=1);

namespace App\Infrastructure;

use App\Domain\Customer;
use App\Domain\DeliveryArea;
use App\Domain\MadeToOrderProduct;
use App\Domain\OrderItem;
use App\Domain\Product;
use App\Domain\StandardProduct;
use App\Domain\Supplier;
use Raoh\Decoder;
use Raoh\Path;
use Raoh\Result;

use function Raoh\Boundary\Array_\{combine, enum_of, field, int_, nested, nullable, string_};

/**
 * Decoders for mapping database rows to domain objects.
 *
 * DB rows are cast to arrays and decoded through Raoh's Array_ boundary,
 * treating the database as just another boundary input source.
 */
final class RowDecoders
{
    /** @return Decoder<array<string, mixed>, Customer> */
    public static function customer(): Decoder
    {
        return combine(
            field('id', string_()),
            field('name', string_()),
            field('email', string_()),
        )->map(fn(string $id, string $name, string $email) => new Customer($id, $name, $email));
    }

    /** @return Decoder<array<string, mixed>, Product> */
    public static function product(): Decoder
    {
        return combine(
            field('id', string_()),
            field('name', string_()),
            field('price', int_()),
            field('delivery_area', enum_of(DeliveryArea::class)),
            field('product_type', string_()),
            field('supplier_id', nullable(string_())),
            field('supplier_name', nullable(string_())),
        )->flatMap(self::buildProduct(...));
    }

    /**
     * Product decoder for joined rows where columns are aliased
     * (e.g. product_id, product_name).
     *
     * @return Decoder<array<string, mixed>, Product>
     */
    public static function joinedProduct(): Decoder
    {
        return combine(
            field('product_id', string_()),
            field('product_name', string_()),
            field('price', int_()),
            field('delivery_area', enum_of(DeliveryArea::class)),
            field('product_type', string_()),
            field('supplier_id', nullable(string_())),
            field('supplier_name', nullable(string_())),
        )->flatMap(self::buildProduct(...));
    }

    /**
     * Order item decoder for joined rows (quantity + product columns).
     *
     * @return Decoder<array<string, mixed>, OrderItem>
     */
    public static function joinedOrderItem(): Decoder
    {
        return combine(
            field('quantity', int_()),
            nested(self::joinedProduct()),
        )->map(fn(int $quantity, Product $product) => new OrderItem($product, $quantity));
    }

    private static function buildProduct(
        string $id,
        string $name,
        int $price,
        DeliveryArea $deliveryArea,
        string $productType,
        ?string $supplierId,
        ?string $supplierName,
    ): Result {
        return match ($productType) {
            'standard' => Result::ok(new StandardProduct($id, $name, $price, $deliveryArea)),
            'made_to_order' => Result::ok(new MadeToOrderProduct(
                $id, $name, $price, $deliveryArea,
                new Supplier($supplierId, $supplierName),
            )),
            default => Result::fail(
                Path::of('product_type'),
                'invalid_value',
                "Unknown product type: {$productType}",
            ),
        };
    }
}
