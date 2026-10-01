<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ActivityLogService;
use App\Services\WooCommerce\WooCommerceClient;
use Illuminate\Http\Request;

class WooCommerceController extends Controller
{
    public function index(WooCommerceClient $client)
    {
        $config = $client->getMaskedConfig();
        $testResult = session('test_result');

        return view('admin.woocommerce.index', compact('config', 'testResult'));
    }

    public function testConnection(WooCommerceClient $client)
    {
        $result = $client->testConnection();

        ActivityLogService::log(
            'test_connection',
            'woocommerce',
            "Tested WooCommerce connection: " . ($result['success'] ? 'Success' : 'Failed - ' . $result['message'])
        );

        if ($result['success']) {
            return back()->with('success', $result['message'])->with('test_result', $result);
        }

        return back()->with('error', $result['message'])->with('test_result', $result);
    }
}
