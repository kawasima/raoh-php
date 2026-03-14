<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Behavior\AcceptDeliveryRequest;
use App\Behavior\SelectDeliveryProvider;
use App\Domain\DeliveryRequest;
use App\Gateway\DeliveryProviderGateway;
use App\Gateway\OrderRepository;
use App\Http\Decoders\OrderDecoders;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Raoh\Err;
use Raoh\Ok;

/**
 * [Example] DeliveryAddressController — late delivery registration
 *
 * This controller handles a second-phase request: attaching a delivery address
 * to an order that was created earlier (e.g. a made-to-order item that has now
 * finished production and is ready to ship).
 *
 * The flow mirrors the delivery branch in OrderController, but the Order is
 * fetched by ID instead of being constructed fresh from the request body.
 * This separates the "what to order" concern (handled at creation time) from
 * the "where to deliver" concern (handled here).
 *
 * raoh-php is used in the same way:
 *   - deliveryAddressRequest() decodes and validates the JSON body
 *   - AcceptDeliveryRequest enforces the DomesticOnly constraint as a Result
 *   - SelectDeliveryProvider enforces the weekday-only constraint as a Result
 *   - No raw strings reach the domain layer
 */
class DeliveryAddressController extends Controller
{
    public function __construct(
        private readonly OrderDecoders           $decoders,
        private readonly AcceptDeliveryRequest   $acceptDeliveryRequest,
        private readonly SelectDeliveryProvider  $selectDeliveryProvider,
        private readonly DeliveryProviderGateway $deliveryProviderGateway,
        private readonly OrderRepository         $orderRepository,
    ) {}

    /**
     * POST /api/v2/orders/{orderId}/delivery-address
     *
     * Step 1: Fetch the existing order — 404 if not found.
     *
     * Step 2: Decode the request body (desiredDeliveryDate + address).
     *         Errors are accumulated and returned as a 422 if invalid.
     *
     * Step 3: Apply delivery constraints via behavior classes.
     *         Both AcceptDeliveryRequest and SelectDeliveryProvider return
     *         Result<T>, so business-rule failures surface as structured errors
     *         rather than exceptions thrown from deep inside the domain.
     */
    public function store(Request $request, string $orderId): JsonResponse
    {
        // Step 1: look up the order — must exist before we can attach an address
        $order = $this->orderRepository->findById($orderId);
        if ($order === null) {
            return response()->json(['error' => 'Order not found.'], 404);
        }

        // Step 2: boundary decode — date format, future-date constraint, blank checks
        $result = $this->decoders->deliveryAddressRequest()->decode($request->getContent());

        if ($result instanceof Err) {
            return response()->json(
                ['errors' => $result->issues->flatten()],
                400,
            );
        }

        assert($result instanceof Ok);
        ['desiredDeliveryDate' => $date, 'address' => $address] = $result->value;

        $deliveryRequest = new DeliveryRequest($date, $address);

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
        $this->orderRepository->save($awaitingResult->value);

        return response()->json(null, 200);
    }
}
