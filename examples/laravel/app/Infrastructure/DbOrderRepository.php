<?php

declare(strict_types=1);

namespace App\Infrastructure;

use App\Domain\Customer;
use App\Domain\Order;
use App\Domain\OrderAwaitingDelivery;
use App\Domain\OrderItem;
use App\Domain\ProductionOrder;
use App\Gateway\OrderRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Raoh\Result;

final class DbOrderRepository implements OrderRepository
{
    public function save(Order|OrderAwaitingDelivery|ProductionOrder $order): string
    {
        $baseOrder = match (true) {
            $order instanceof OrderAwaitingDelivery => $order->order,
            $order instanceof ProductionOrder => $order->order,
            default => $order,
        };

        $id = Str::uuid()->toString();

        $status = match (true) {
            $order instanceof OrderAwaitingDelivery => 'awaiting_delivery',
            $order instanceof ProductionOrder => 'production',
            default => 'created',
        };

        $customerRow = RowEncoders::customer()->encode($baseOrder->customer);
        DB::table('orders')->insert([
            'id' => $id,
            'customer_id' => $customerRow['id'],
            'status' => $status,
        ]);

        $itemEncoder = RowEncoders::orderItem();
        foreach ($baseOrder->items as $item) {
            DB::table('order_items')->insert(
                array_merge(['order_id' => $id], $itemEncoder->encode($item))
            );
        }

        return $id;
    }

    public function findById(string $orderId): ?Order
    {
        $row = DB::table('orders')
            ->join('customers', 'orders.customer_id', '=', 'customers.id')
            ->where('orders.id', $orderId)
            ->select(
                'customers.id',
                'customers.name',
                'customers.email',
            )
            ->first();

        if ($row === null) {
            return null;
        }

        /** @var Customer $customer */
        $customer = RowDecoders::customer()->decode((array) $row)->getOrThrow();

        $itemRows = DB::table('order_items')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->leftJoin('suppliers', 'products.supplier_id', '=', 'suppliers.id')
            ->where('order_items.order_id', $orderId)
            ->select(
                'products.id as product_id',
                'products.name as product_name',
                'products.price',
                'products.delivery_area',
                'products.product_type',
                'products.supplier_id',
                'suppliers.name as supplier_name',
                'order_items.quantity',
            )
            ->get();

        $decoder = RowDecoders::joinedOrderItem();

        /** @var Result<list<OrderItem>> $itemsResult */
        $itemsResult = Result::traverse(
            $itemRows->all(),
            fn(object $r) => $decoder->decode((array) $r),
        );

        /** @var list<OrderItem> $items */
        $items = $itemsResult->getOrThrow();

        return new Order($customer, $items);
    }
}
