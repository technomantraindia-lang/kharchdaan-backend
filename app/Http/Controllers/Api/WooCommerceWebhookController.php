<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\WooCommerce\WooCommerceWebhookService;
use Illuminate\Http\Request;

class WooCommerceWebhookController extends Controller
{
    public function handle(Request $request, WooCommerceWebhookService $webhookService)
    {
        if (! $webhookService->verifySignature($request)) {
            return response()->json(['error' => 'Unauthorized: Invalid WooCommerce webhook signature'], 401);
        }

        $result = $webhookService->processRequest($request);

        return response()->json([
            'success' => $result['success'],
            'message' => $result['message'],
        ], $result['status'] ?? 200);
    }
}
