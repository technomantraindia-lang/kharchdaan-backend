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
            route('admin.dashboard'),
            route('admin.mlm.members.index'),
            route('admin.mlm.members.show', $this->member),
            route('admin.mlm.sponsor-network'),
            route('admin.mlm.sponsor-network', ['root' => $this->member->id]),
            route('admin.mlm.tree.index'),
            route('admin.mlm.genealogy'),
            route('admin.mlm.genealogy', ['root' => $this->member->id]),
            route('admin.mlm.levels'),
            route('admin.mlm.calculations.index'),
            route('admin.mlm.reconciliation.index'),
            route('admin.mlm.payouts.index'),
            route('admin.cashback.index'),
            route('admin.cashback.reconciliation'),
            route('admin.reports.index'),
            route('admin.reports.network'),
            route('admin.reports.income'),
            route('admin.reports.payouts'),
            route('admin.reports.cashback'),
            route('admin.reports.profit'),
            route('admin.reports.gst'),
            route('admin.customers.index'),
            route('admin.orders.index'),
            route('admin.users.index'),
            route('admin.activity-logs.index'),
            route('admin.settings.index'),
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
