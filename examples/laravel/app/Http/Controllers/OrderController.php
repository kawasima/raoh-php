<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Behavior\AcceptDeliveryRequest;
use App\Behavior\AcceptOrder;
use App\Behavior\ClassifyOrderItems;
use App\Behavior\InstructProduction;
use App\Behavior\SelectDeliveryProvider;
use App\Domain\DeliveryRequest;
use App\Gateway\DeliveryProviderGateway;
use App\Gateway\OrderRepository;
use App\Http\Decoders\OrderDecoders;
use DateTimeImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Raoh\Err;
use Raoh\Ok;

/**
 * [Example] OrderController — Parse Don't Validate at the boundary
 *
 * This controller demonstrates the raoh-php approach to handling HTTP input:
 *
 *   1. Decode — raw JSON is decoded by OrderDecoders::createOrderRequest().
 *      If the input is invalid, ALL field errors are accumulated and returned
 *      in one response (422). No partially-valid data ever enters the domain.
 *
 *   2. Route — once decoding succeeds, the typed values are handed to behavior
 *      classes (AcceptOrder, ClassifyOrderItems, …). The controller itself
 *      contains no business logic — it only orchestrates.
 *
 *   3. Branch on product type — orders containing made-to-order items are
 *      routed to production (InstructProduction → ProductionOrder), while
 *      standard-only orders proceed directly to delivery scheduling
 *      (AcceptDeliveryRequest → OrderAwaitingDelivery).
 *
 *   4. Behavior results are also Result<T> values, not exceptions. Each
 *      business-rule violation (e.g. DomesticOnly shipped overseas, weekend
 *      provider conflict) returns an Err with a structured Issue, which is
 *      surfaced to the caller as a 422 with a path-keyed error map.
 */
class OrderController extends Controller
{
    public function __construct(
        private readonly OrderDecoders          $decoders,
        private readonly AcceptOrder            $acceptOrder,
        private readonly ClassifyOrderItems     $classifyOrderItems,
        private readonly AcceptDeliveryRequest  $acceptDeliveryRequest,
        private readonly SelectDeliveryProvider $selectDeliveryProvider,
        private readonly InstructProduction     $instructProduction,
        private readonly DeliveryProviderGateway $deliveryProviderGateway,
        private readonly OrderRepository        $orderRepository,
    ) {}

    /**
     * POST /api/v2/orders
     *
     * Step 1: Decode the raw JSON body.
     *         All field errors are collected in one pass — the caller receives
     *         every problem at once, not just the first one.
     *
     * Step 2: Classify items and accept the order.
     *         ClassifyOrderItems separates StandardProduct and MadeToOrderProduct
     *         items so the routing decision below stays free of isinstance checks.
     *
     * Step 3a (made-to-order): Instruct production.
     *         InstructProduction returns Err if no MadeToOrderProduct is present,
     *         keeping the invariant inside the behavior class rather than here.
     *
     * Step 3b (standard): Accept delivery request and select provider.
     *         AcceptDeliveryRequest enforces the DomesticOnly constraint.
     *         SelectDeliveryProvider enforces the weekday-only constraint.
     *         Both return Result<T> so failures surface as structured 422 errors.
     */
    public function store(Request $request): JsonResponse
    {
        // Step 1: boundary decode — invalid input never reaches the domain
        $result = $this->decoders->createOrderRequest()->decode($request->getContent());

        if ($result instanceof Err) {
            return response()->json(
                ['errors' => $result->issues->flatten()],
                400,
            );
        }

        assert($result instanceof Ok);
        [$customer, $items, $address, $desiredDeliveryDate] = $result->value;

        // Step 2: classify items and construct the Order aggregate
        $classified = ($this->classifyOrderItems)($items);
        $order      = ($this->acceptOrder)($items, $customer);

        if ($classified->hasMadeToOrderItems()) {
            // Step 3a: made-to-order path — route to supplier production
            $productionResult = ($this->instructProduction)(
                $order,
                (new DateTimeImmutable('+14 days'))->setTime(0, 0),
            );

            if ($productionResult instanceof Err) {
                return response()->json(
                    ['errors' => $productionResult->issues->flatten()],
                    422,
                );
            }

            assert($productionResult instanceof Ok);
            $orderId = $this->orderRepository->save($productionResult->value);

            return response()->json(['orderId' => $orderId]);
        }

        // Step 3b: standard path — validate delivery constraints and schedule
        $deliveryRequest = new DeliveryRequest($desiredDeliveryDate, $address);

        // Business rule: DomesticOnly products cannot be shipped outside Japan
        $awaitingResult = ($this->acceptDeliveryRequest)($order, $deliveryRequest);
        if ($awaitingResult instanceof Err) {
            return response()->json(
                ['errors' => $awaitingResult->issues->flatten()],
                422,
            );
        }

        $provider = $this->deliveryProviderGateway->selectProvider($deliveryRequest);

        // Business rule: WeekdaysOnly providers cannot deliver on weekends
        $providerResult = ($this->selectDeliveryProvider)($deliveryRequest, $provider);

        if ($providerResult instanceof Err) {
            return response()->json(
                ['errors' => $providerResult->issues->flatten()],
                422,
            );
        }

        assert($awaitingResult instanceof Ok);
        $orderId = $this->orderRepository->save($awaitingResult->value);

        return response()->json(['orderId' => $orderId]);
    }
}
