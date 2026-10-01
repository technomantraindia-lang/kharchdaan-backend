<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\Order;
use App\Models\Setting;
use App\Models\User;
use App\Services\ReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GstStateCalculationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Setting::updateOrCreate(['key' => 'seller_state'], ['value' => 'Gujarat']);
    }

    public function test_same_state_calculates_cgst_and_sgst(): void
    {
        Order::query()->delete();
        $user = User::first();
        Address::create([
            'user_id' => $user->id,
            'type' => 'billing',
            'fname' => 'John',
            'address' => '123 Street',
            'city' => 'Ahmedabad',
            'state' => 'Gujarat',
            'zip' => '380001',
            'phone' => '9999999999',
        ]);

        Order::create([
            'order_num' => 'ORD-GST-001',
            'user_id' => $user->id,
            'subtotal' => 100,
            'gst_amt' => 18,
            'total' => 118,
            'pay_status' => 'paid',
            'seller_state' => 'Gujarat',
            'customer_state' => 'Gujarat',
        ]);

        $service = new ReportService();
        $range = $service->resolveDateRange('this_month', null, null);
        $report = $service->getGstReport($range);

        $this->assertEquals(18.00, $report['total_gst']);
        $this->assertEquals(9.00, $report['total_cgst']);
        $this->assertEquals(9.00, $report['total_sgst']);
        $this->assertEquals(0.00, $report['total_igst']);
    }

    public function test_interstate_calculates_igst(): void
    {
        Order::query()->delete();
        Address::query()->delete();
        $user = User::first();

        Order::create([
            'order_num' => 'ORD-GST-002',
            'user_id' => $user->id,
            'subtotal' => 100,
            'gst_amt' => 18,
            'total' => 118,
            'pay_status' => 'paid',
            'seller_state' => 'Gujarat',
            'customer_state' => 'Maharashtra',
        ]);

        $service = new ReportService();
        $range = $service->resolveDateRange('this_month', null, null);
        $report = $service->getGstReport($range);

        $this->assertEquals(18.00, $report['total_gst']);
        $this->assertEquals(0.00, $report['total_cgst']);
        $this->assertEquals(0.00, $report['total_sgst']);
        $this->assertEquals(18.00, $report['total_igst']);
    }
}
