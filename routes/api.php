<?php

use App\Http\Controllers\Api\V1\AttributeApiController;
use App\Http\Controllers\Api\V1\BrandApiController;
use App\Http\Controllers\Api\V1\CategoryApiController;
use App\Http\Controllers\Api\V1\CustomerAuthApiController;
use App\Http\Controllers\Api\V1\CustomerOrderApiController;
use App\Http\Controllers\Api\V1\ProductApiController;
use App\Http\Controllers\Api\WooCommerceWebhookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| WooCommerce Webhook API Route (CSRF Exempt)
|--------------------------------------------------------------------------
*/
Route::post('/webhooks/woocommerce', [WooCommerceWebhookController::class, 'handle'])
    ->middleware('throttle:webhooks')
    ->name('api.webhooks.woocommerce');

/*
|--------------------------------------------------------------------------
| Public API V1 Routes
|--------------------------------------------------------------------------
*/
Route::prefix('v1')->middleware('throttle:api')->group(function () {

    // Public Product & Catalog APIs
    Route::get('/products', [ProductApiController::class, 'index']);
    Route::get('/products/{slug}', [ProductApiController::class, 'show']);
    Route::get('/products/{product}/variations', [ProductApiController::class, 'variations']);

    Route::get('/categories', [CategoryApiController::class, 'index']);
    Route::get('/categories/{slug}', [CategoryApiController::class, 'show']);

    Route::get('/brands', [BrandApiController::class, 'index']);
    Route::get('/attributes', [AttributeApiController::class, 'index']);

    // Customer Authentication APIs (Strictly Rate-Limited)
    Route::post('/register', [CustomerAuthApiController::class, 'register'])->middleware('throttle:login');
    Route::post('/login', [CustomerAuthApiController::class, 'login'])->middleware('throttle:login');

    // Authenticated Customer APIs
    Route::middleware(\App\Http\Middleware\AuthenticateApiToken::class)->group(function () {
        Route::post('/logout', [CustomerAuthApiController::class, 'logout']);
        Route::get('/me', [CustomerAuthApiController::class, 'me']);
        Route::put('/me', [CustomerAuthApiController::class, 'updateProfile']);

        Route::get('/orders', [CustomerOrderApiController::class, 'index']);
        Route::get('/orders/{id}', [CustomerOrderApiController::class, 'show']);
    });
});
