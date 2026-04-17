<?php

declare(strict_types=1);

namespace App\Infrastructure;

use App\Domain\Customer;
use App\Domain\OrderItem;
use Raoh\Encoder;
use function Raoh\Boundary\Array_\Encode\int_;
use function Raoh\Boundary\Array_\Encode\object_;
use function Raoh\Boundary\Array_\Encode\property;
use function Raoh\Boundary\Array_\Encode\string_;

/**
 * Encoders for converting domain objects to database row arrays.
 *
 * Symmetric counterpart of RowDecoders — where RowDecoders maps DB rows
 * to domain objects, RowEncoders maps domain objects back to arrays
 * suitable for DB::table()->insert().
 */
final class RowEncoders
{
    /** @return Encoder<Customer, array<string, mixed>> */
    public static function customer(): Encoder
    {
        return object_(
            property('id',    fn(Customer $c): string => $c->customerId, string_()),
            property('name',  fn(Customer $c): string => $c->name,       string_()),
            property('email', fn(Customer $c): string => $c->email,      string_()),
        );
    }

    /** @return Encoder<OrderItem, array<string, mixed>> */
    public static function orderItem(): Encoder
    {
        return object_(
            property('product_id', fn(OrderItem $i): string => $i->product->productId, string_()),
            property('quantity',   fn(OrderItem $i): int    => $i->quantity,            int_()),
        );
    }
}
