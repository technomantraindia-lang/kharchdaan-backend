<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\Order;
use App\Models\Role;
use App\Models\User;
use App\Services\Mlm\CustomerTransactionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerDownlineActivityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'Customer', 'guard_name' => 'web']);
    }

    private function makeMember(string $memberId, ?Member $sponsor = null): array
    {
        $role = Role::where('name', 'Customer')->firstOrFail();
        $user = User::create([
            'name' => 'Member '.$memberId,
            'email' => strtolower($memberId).'@example.com',
            'password' => 'password',
            'phone' => '9'.str_pad((string) random_int(0, 999999999), 9, '0', STR_PAD_LEFT),
            'role_id' => $role->id,
            'status' => 'active',
            'mlm_member_id' => $memberId,
        ]);

        $member = Member::create([
            'user_id' => $user->id,
            'sponsor_member_id' => $sponsor?->id,
            'placement_parent_id' => $sponsor?->id,
            'placement_position' => $sponsor ? 'left' : null,
            'status' => Member::STATUS_ACTIVE,
            'joined_at' => now(),
        ]);

        return [$user, $member];
    }

    private function order(User $user, string $num, string $total, string $status = 'delivered'): void
    {
        Order::create([
            'order_num' => $num,
            'user_id' => $user->id,
            'subtotal' => $total,
            'discount' => 0,
            'ship_charge' => 0,
            'total' => $total,
            'status' => $status,
            'pay_status' => 'paid',
            'currency' => 'INR',
            'ship_addr' => 'Ahmedabad',
            'bill_addr' => 'Ahmedabad',
            'order_channel' => 'online',
            'special_instruction' => 'Downline fixture',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_downline_transactions_mark_below_threshold_as_red(): void
    {
        [$sponsorUser, $sponsor] = $this->makeMember('C25110001');
        [$downlineUser] = $this->makeMember('C25110002', $sponsor);

        $this->order($downlineUser, 'T-DL-SMALL', '150');
        $this->order($downlineUser, 'T-DL-BIG', '1000');
        $this->order($downlineUser, 'T-DL-CANCEL', '1500', 'cancelled');

        $payload = app(CustomerTransactionService::class)->forUser($sponsorUser, [])['downline'];

        $this->assertSame(1, $payload['direct_count']);
        $this->assertCount(3, $payload['transactions']);

        $byTxn = collect($payload['transactions'])->keyBy('transaction_id');

        $this->assertTrue($byTxn['T-DL-SMALL']['highlight_red']);
        $this->assertFalse($byTxn['T-DL-SMALL']['cashback_eligible']);

        $this->assertFalse($byTxn['T-DL-BIG']['highlight_red']);
        $this->assertTrue($byTxn['T-DL-BIG']['cashback_eligible']);

        $this->assertTrue($byTxn['T-DL-CANCEL']['highlight_red']);
        $this->assertSame('C25110002', $byTxn['T-DL-BIG']['member_customer_id']);
    }

    public function test_downline_is_empty_for_member_without_referrals(): void
    {
        [$user] = $this->makeMember('C25110010');

        $payload = app(CustomerTransactionService::class)->forUser($user, [])['downline'];

        $this->assertSame(0, $payload['direct_count']);
        $this->assertSame([], $payload['transactions']);
        $this->assertSame([], $payload['monthly_volume']);
    }
}
