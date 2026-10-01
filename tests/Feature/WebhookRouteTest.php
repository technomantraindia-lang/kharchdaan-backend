<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class WebhookRouteTest extends TestCase
{
    public function test_single_woocommerce_webhook_route_exists(): void
    {
        $routes = collect(Route::getRoutes())->filter(function ($route) {
            return $route->uri() === 'api/webhooks/woocommerce';
        });

        $this->assertCount(1, $routes, 'Expected exactly one POST /api/webhooks/woocommerce route registration.');
    }
}
