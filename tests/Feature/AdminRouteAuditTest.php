<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminRouteAuditTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Member $member;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
        $customerRole = Role::firstOrCreate(['name' => 'Customer', 'guard_name' => 'web']);

        $this->admin = User::factory()->create([
            'email' => 'admin_audit@example.com',
            'role_id' => $adminRole->id,
            'status' => 'active',
        ]);

        $customerUser = User::factory()->create([
            'email' => 'cust_audit@example.com',
            'role_id' => $customerRole->id,
            'status' => 'active',
            'mlm_member_id' => 'C26090001',
        ]);

        $this->member = Member::create([
            'user_id' => $customerUser->id,
            'status' => Member::STATUS_ACTIVE,
            'kyc_status' => Member::KYC_APPROVED,
            'joined_at' => now(),
        ]);
    }

    public function test_all_admin_routes_return_ok_200(): void
    {
        $routes = [
            admin_route('dashboard'),
            admin_route('mlm.members.index'),
            admin_route('mlm.members.show', $this->member),
            admin_route('mlm.sponsor-network'),
            admin_route('mlm.sponsor-network', ['root' => $this->member->id]),
            admin_route('mlm.tree.index'),
            admin_route('mlm.genealogy'),
            admin_route('mlm.genealogy', ['root' => $this->member->id]),
            admin_route('mlm.levels'),
            admin_route('mlm.calculations.index'),
            admin_route('mlm.reconciliation.index'),
            admin_route('mlm.payouts.index'),
            admin_route('cashback.index'),
            admin_route('cashback.reconciliation'),
            admin_route('reports.index'),
            admin_route('reports.network'),
            admin_route('reports.income'),
            admin_route('reports.payouts'),
            admin_route('reports.cashback'),
            admin_route('reports.profit'),
            admin_route('reports.gst'),
            admin_route('customers.index'),
            admin_route('orders.index'),
            admin_route('users.index'),
            admin_route('activity-logs.index'),
            admin_route('settings.index'),
        ];

        foreach ($routes as $url) {
            $response = $this->actingAs($this->admin)->get($url);
            if ($response->getStatusCode() !== 200) {
                dump("Error on {$url}: " . ($response->exception ? $response->exception->getMessage() . ' in ' . $response->exception->getFile() . ':' . $response->exception->getLine() : $response->getContent()));
            }
            $this->assertSame(200, $response->getStatusCode(), "Failed asserting that {$url} returns HTTP 200. Got: {$response->getStatusCode()}");
        }
    }
}
