# raoh-php Laravel Example

This example shows how to use raoh-php in a Laravel application, ported from the
[jjug-ccc-2025-fall spec-driven sample](https://github.com/kawasima/jjug-ccc-2025-fall).

It demonstrates **Parse Don't Validate** in a real REST API context: boundary decoding, error
accumulation, and domain behavior functions — all wired together with Laravel's IoC container.

## Domain

An order management system that supports two product types and enforces delivery constraints.

```
StandardProduct    — shipped immediately; may be DomesticOnly or International
MadeToOrderProduct — routed to a supplier for production; always DomesticOnly
```

Business rules encoded as behavior classes:

| Class | Rule |
|---|---|
| `AcceptDeliveryRequest` | DomesticOnly products cannot ship outside Japan |
| `SelectDeliveryProvider` | WeekdaysOnly providers cannot deliver on weekends |
| `InstructProduction` | Production orders require at least one MadeToOrderProduct |

## Setup

```bash
composer install
php artisan serve
```

No database is required. Products and customers are in-memory stubs. Orders are persisted
across requests using Laravel's file cache.

## API

### POST /api/v2/orders

Create an order. Routes to production or delivery depending on product type.

```bash
# Standard product (domestic) - OrderAwaitingDelivery
curl -X POST http://localhost:8000/api/v2/orders \
  -H "Content-Type: application/json" \
  -d '{
    "customerId": "CUST-001",
    "items": [{"productId": "PROD-001", "quantity": 2}],
    "country": "JP",
    "postalCd": "100-0001",
    "address1": "Tokyo",
    "address2": "Chiyoda Ward",
    "desiredDeliveryDate": "2026-04-01"
  }'
# {"orderId":"..."}

# Made-to-order product - ProductionOrder
curl -X POST http://localhost:8000/api/v2/orders \
  -H "Content-Type: application/json" \
  -d '{
    "customerId": "CUST-001",
    "items": [{"productId": "PROD-003", "quantity": 1}],
    "country": "JP",
    "postalCd": "100-0001",
    "address1": "Tokyo",
    "address2": "Chiyoda Ward",
    "desiredDeliveryDate": "2026-04-01"
  }'
# {"orderId":"..."}
```

Validation errors are accumulated across all fields:

```bash
curl -X POST http://localhost:8000/api/v2/orders \
  -H "Content-Type: application/json" \
  -d '{
    "customerId": "CUST-999",
    "items": [{"productId": "PROD-001", "quantity": 0}],
    "country": "JP",
    "postalCd": "",
    "address1": "Tokyo",
    "address2": "",
    "desiredDeliveryDate": "2020-01-01"
  }'
# {
#   "errors": {
#     "/customerId":          ["Customer 'CUST-999' not found."],
#     "/items/0/quantity":    ["must be positive"],
#     "/postalCd":            ["must not be blank"],
#     "/desiredDeliveryDate": ["Desired delivery date must be in the future."]
#   }
# }
```

Business rule violation (DomesticOnly product shipped overseas):

```bash
curl -X POST http://localhost:8000/api/v2/orders \
  -H "Content-Type: application/json" \
  -d '{
    "customerId": "CUST-001",
    "items": [{"productId": "PROD-001", "quantity": 1}],
    "country": "US",
    "postalCd": "10001",
    "address1": "New York",
    "address2": "Manhattan",
    "desiredDeliveryDate": "2026-04-01"
  }'
# {"errors":{"/country":["This product can only be delivered within Japan."]}}
```

### POST /api/v2/orders/{orderId}/delivery-address

Register a delivery address for an existing order.

```bash
ORDER_ID=<id from above>

curl -X POST "http://localhost:8000/api/v2/orders/$ORDER_ID/delivery-address" \
  -H "Content-Type: application/json" \
  -d '{
    "desiredDeliveryDate": "2026-04-02",
    "country": "JP",
    "postalCd": "100-0002",
    "address1": "Osaka",
    "address2": "Namba"
  }'
# {} (200 OK)
```

## Seed Data

**Customers**

| ID | Name |
|---|---|
| `CUST-001` | Alice Tanaka |
| `CUST-002` | Bob Yamamoto |
| `CUST-003` | Carol Suzuki |

**Products**

| ID | Name | Type | Delivery |
|---|---|---|---|
| `PROD-001` | Widget A | Standard | DomesticOnly |
| `PROD-002` | Gadget B | Standard | International |
| `PROD-003` | Custom Frame C | MadeToOrder | DomesticOnly |

## How raoh-php Is Used

### Decoder definition

```php
// app/Http/Decoders/OrderDecoders.php

public function createOrderRequest(): Decoder
{
    return from_json(combine(
        field('customerId', $this->customer()),
        field('items', list_of($this->orderItem())),
        nested($this->address()),
        field('desiredDeliveryDate', $this->desiredDeliveryDate()),
    )->map(fn($customer, $items, $address, $date) => [$customer, $items, $address, $date]));
}
```

- `combine(...)` runs all field decoders independently and accumulates errors
- `flatMap(...)` inside `orderItem()` does a repository lookup and returns `Result::fail` if not found
- `from_json(...)` wraps the array decoder to accept a raw JSON request body

### Controller

```php
$result = $this->decoders->createOrderRequest()->decode($request->getContent());

if ($result instanceof Err) {
    return response()->json(['errors' => $result->issues->flatten()], 400);
}

[$customer, $items, $address, $desiredDeliveryDate] = $result->value;
// All values are typed domain objects here — no raw strings remain
```

### Behavior functions

Domain rules return `Result` instead of throwing:

```php
class AcceptDeliveryRequest
{
    public function __invoke(Order $order, DeliveryRequest $deliveryRequest): Result
    {
        foreach ($order->items as $item) {
            if ($item->product->deliveryArea === DeliveryArea::DomesticOnly) {
                if ($deliveryRequest->deliveryAddress->countryCode !== 'JP') {
                    return Result::fail(
                        Path::of('country'),
                        'domestic_only',
                        'This product can only be delivered within Japan.',
                    );
                }
            }
        }
        return Result::ok(new OrderAwaitingDelivery($order, $deliveryRequest));
    }
}
```

### Service container

```php
// app/Providers/AppServiceProvider.php

$this->app->singleton(ProductRepository::class, InMemoryProductRepository::class);
$this->app->singleton(CustomerRepository::class, InMemoryCustomerRepository::class);
$this->app->singleton(OrderRepository::class, InMemoryOrderRepository::class);
$this->app->singleton(DeliveryProviderGateway::class, StubDeliveryProviderGateway::class);

$this->app->bind(OrderDecoders::class, fn($app) => new OrderDecoders(
    $app->make(ProductRepository::class),
    $app->make(CustomerRepository::class),
));
```

All dependencies are resolved via Laravel's container. Replacing `InMemory*` stubs with real
database-backed implementations requires no changes to the controllers or decoders.
