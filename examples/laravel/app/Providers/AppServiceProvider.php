<?php

declare(strict_types=1);

namespace App\Providers;

use App\Behavior\AcceptDeliveryRequest;
use App\Behavior\AcceptOrder;
use App\Behavior\ClassifyOrderItems;
use App\Behavior\InstructProduction;
use App\Behavior\SelectDeliveryProvider;
use App\Gateway\CustomerRepository;
use App\Gateway\DeliveryProviderGateway;
use App\Gateway\InMemoryCustomerRepository;
use App\Gateway\InMemoryOrderRepository;
use App\Gateway\InMemoryProductRepository;
use App\Gateway\OrderRepository;
use App\Gateway\ProductRepository;
use App\Gateway\StubDeliveryProviderGateway;
use App\Http\Decoders\OrderDecoders;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Repositories (singletons so in-memory state persists within a request)
        $this->app->singleton(ProductRepository::class, InMemoryProductRepository::class);
        $this->app->singleton(CustomerRepository::class, InMemoryCustomerRepository::class);
        $this->app->singleton(OrderRepository::class, InMemoryOrderRepository::class);
        $this->app->singleton(DeliveryProviderGateway::class, StubDeliveryProviderGateway::class);

        // Behaviors
        $this->app->bind(AcceptOrder::class);
        $this->app->bind(ClassifyOrderItems::class);
        $this->app->bind(AcceptDeliveryRequest::class);
        $this->app->bind(SelectDeliveryProvider::class);
        $this->app->bind(InstructProduction::class);

        // Decoders
        $this->app->bind(OrderDecoders::class, fn($app) => new OrderDecoders(
            $app->make(ProductRepository::class),
            $app->make(CustomerRepository::class),
        ));
    }

    public function boot(): void {}
}
