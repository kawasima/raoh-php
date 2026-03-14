<?php

declare(strict_types=1);

namespace App\Http\Decoders;

use App\Domain\Address;
use App\Domain\Customer;
use App\Domain\OrderItem;
use App\Gateway\CustomerRepository;
use App\Gateway\ProductRepository;
use DateTimeImmutable;
use Raoh\CallableDecoder;
use Raoh\Decoder;
use Raoh\Path;
use Raoh\Result;

use function Raoh\Boundary\Json\{combine, field, from_json, int_, list_of, nested, string_};

class OrderDecoders
{
    public function __construct(
        private readonly ProductRepository  $products,
        private readonly CustomerRepository $customers,
    ) {}

    /** @return Decoder<mixed, Address> */
    public function address(): Decoder
    {
        return combine(
            field('country', string_()->nonBlank()),
            field('postalCd', string_()->nonBlank()),
            field('address1', string_()->nonBlank()),
            field('address2', string_()),
        )->map(fn($country, $postalCd, $address1, $address2) =>
            new Address($country, $postalCd, $address1, $address2)
        );
    }

    /** @return Decoder<mixed, OrderItem> */
    public function orderItem(): Decoder
    {
        return combine(
            field('productId', string_()->nonBlank()),
            field('quantity', int_()->positive()),
        )->flatMap(function (string $productId, int $quantity) {
            $product = $this->products->findById($productId);
            if ($product === null) {
                return Result::fail(
                    Path::of('productId'),
                    'not_found',
                    "Product '{$productId}' not found.",
                );
            }
            return Result::ok(new OrderItem($product, $quantity));
        });
    }

    /** @return Decoder<mixed, Customer> */
    public function customer(): Decoder
    {
        return CallableDecoder::of(function (mixed $in, Path $path) {
            if (!is_string($in) || $in === '') {
                return Result::fail($path, 'required', 'Customer ID is required.');
            }
            $customer = $this->customers->findById($in);
            if ($customer === null) {
                return Result::fail($path, 'not_found', "Customer '{$in}' not found.");
            }
            return Result::ok($customer);
        });
    }

    /** @return Decoder<mixed, DateTimeImmutable> */
    public function desiredDeliveryDate(): Decoder
    {
        return string_()->nonBlank()->flatMap(function (string $value) {
            $date = DateTimeImmutable::createFromFormat('Y-m-d', $value);
            if ($date === false) {
                return Result::fail(Path::root(), 'invalid_format', 'Date must be in YYYY-MM-DD format.');
            }
            if ($date <= new DateTimeImmutable('today')) {
                return Result::fail(Path::root(), 'out_of_range', 'Desired delivery date must be in the future.');
            }
            return Result::ok($date);
        });
    }

    /**
     * Decoder for POST /api/v2/orders
     *
     * @return Decoder<string, array{Customer, list<OrderItem>, Address, DateTimeImmutable}>
     */
    public function createOrderRequest(): Decoder
    {
        return from_json(combine(
            field('customerId', $this->customer()),
            field('items', list_of($this->orderItem())),
            nested($this->address()),
            field('desiredDeliveryDate', $this->desiredDeliveryDate()),
        )->map(fn($customer, $items, $address, $date) => [$customer, $items, $address, $date]));
    }

    /**
     * Decoder for POST /api/v2/orders/{orderId}/delivery-address
     *
     * @return Decoder<string, array{desiredDeliveryDate: DateTimeImmutable, address: Address}>
     */
    public function deliveryAddressRequest(): Decoder
    {
        return from_json(combine(
            field('desiredDeliveryDate', $this->desiredDeliveryDate()),
            nested($this->address()),
        )->map(fn($date, $address) => ['desiredDeliveryDate' => $date, 'address' => $address]));
    }
}
