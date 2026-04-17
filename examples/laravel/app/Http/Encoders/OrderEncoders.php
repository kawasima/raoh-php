<?php

declare(strict_types=1);

namespace App\Http\Encoders;

use App\Domain\Customer;
use App\Domain\Order;
use App\Domain\OrderItem;
use Raoh\Encoder;

use function Raoh\Boundary\Array_\Encode\int_;
use function Raoh\Boundary\Array_\Encode\list_;
use function Raoh\Boundary\Array_\Encode\nested;
use function Raoh\Boundary\Array_\Encode\object_;
use function Raoh\Boundary\Array_\Encode\property;
use function Raoh\Boundary\Array_\Encode\string_;

/**
 * Encoders for converting domain objects to HTTP response arrays.
 *
 * Symmetric counterpart of OrderDecoders — where OrderDecoders turns
 * raw JSON into typed domain objects, OrderEncoders turns domain objects
 * back into arrays that Laravel's response()->json() can serialize.
 */
final class OrderEncoders
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
            property('productId', fn(OrderItem $i): string => $i->product->productId, string_()),
            property('name',      fn(OrderItem $i): string => $i->product->name,      string_()),
            property('price',     fn(OrderItem $i): int    => $i->product->price,     int_()),
            property('quantity',  fn(OrderItem $i): int    => $i->quantity,           int_()),
        );
    }

    /** @return Encoder<Order, array<string, mixed>> */
    public static function order(): Encoder
    {
        return object_(
            property('customer', fn(Order $o): Customer   => $o->customer, nested(self::customer())),
            property('items',    fn(Order $o): array      => $o->items,    list_(nested(self::orderItem()))),
        );
    }
}
