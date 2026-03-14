<?php

declare(strict_types=1);

use App\Http\Controllers\DeliveryAddressController;
use App\Http\Controllers\OrderController;
use Illuminate\Support\Facades\Route;

Route::post('/v2/orders', [OrderController::class, 'store']);
Route::post('/v2/orders/{orderId}/delivery-address', [DeliveryAddressController::class, 'store']);
