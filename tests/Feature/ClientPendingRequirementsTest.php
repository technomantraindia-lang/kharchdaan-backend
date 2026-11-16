<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\Order;
use App\Models\Role;
use App\Models\User;
use App\Services\Mlm\PurchaseEligibilityService;
use App\Services\Order\OrderTransactionIdGenerator;
use App\Support\MlmDecimal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ClientPendingRequirementsTest extends TestCase
{
    use RefreshDatabase;

    private Role $customerRole;

    protected function setUp(): void
    {
        parent::setUp();
        $this->customerRole = Role::firstOrCreate(['name' => 'Customer'], ['guard_name' => 'web']);
    }

    public function test_login_accepts_customer_id_and_mobile(): void
    {
        $user = $this->makeCustomer('C25070099', '9988776655', 'secret99');

        $this->postJson('/api/v1/login', [
            'login' => 'C25070099',
            'password' => 'secret99',
        ])->assertOk()->assertJsonPath('success', true);

        $this->postJson('/api/v1/login', [
            'login' => '9988776655',
            'password' => 'secret99',
        ])->assertOk()->assertJsonPath('data.user.email', $user->email);
    }

    public function test_unique_phone_enforced_on_register(): void
    {
        $this->makeCustomer('C25070100', '9000000001', 'password');

        $this->postJson('/api/v1/register', [
            'name' => 'Dup Phone',
            'email' => 'dup-phone@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'phone' => '9000000001',
        ])->assertStatus(422);
    }

    public function test_terms_gate_blocks_dashboard_until_accepted(): void
    {
        $member = $this->makeMember('C25070101', '9000000002');
        $user = $member->user;
        $user->forceFill(['terms_accepted_at' => null])->save();

        $this->actingAs($user)
            ->get(route('network.index'))
            ->assertRedirect(route('terms.show'));

        $this->actingAs($user)
            ->post(route('terms.accept'), ['accept_terms' => '1'])
            ->assertRedirect(route('account'));

        $this->assertNotNull($user->fresh()->terms_accepted_at);

        $this->actingAs($user)
            ->get(route('network.index'))
            ->assertOk();
    }

    public function test_transaction_ids_follow_tymdd_sequence(): void
    {
        $generator = app(OrderTransactionIdGenerator::class);
        $first = $generator->next(now());

        $prefix = 'T'.now()->format('ymd');
        $this->assertSame($prefix.'0001', $first);

        Order::create([
            'order_num' => $first,
            'subtotal' => 100,
            'total' => 100,
            'status' => 'pending',
            'pay_status' => 'pending',
            'currency' => 'INR',
        ]);

        $second = $generator->next(now());
        $this->assertSame($prefix.'0002', $second);
    }

    public function test_cashback_rounds_up_to_next_ten(): void
    {
        $this->assertSame('840.00', MlmDecimal::roundUpToNextTen('831'));
        $this->assertSame('840.00', MlmDecimal::roundUpToNextTen('831.25'));
        $this->assertSame('800.00', MlmDecimal::roundUpToNextTen('800'));
    }

    public function test_direct_selling_and_referral_month_gates(): void
    {
        $member = $this->makeMember('C25070102', '9000000003');
        $eligibility = app(PurchaseEligibilityService::class);

        $this->assertFalse($eligibility->isDirectSellingEligible($member));
        $this->assertFalse($eligibility->isReferralMonthEligible($member));

        Order::create([
            'order_num' => 'T'.now()->format('ymd').'0100',
            'user_id' => $member->user_id,
            'subtotal' => 250,
            'total' => 250,
            'status' => 'delivered',
            'pay_status' => 'paid',
            'currency' => 'INR',
        ]);

        $this->assertTrue($eligibility->isReferralMonthEligible($member->fresh()));
        $this->assertFalse($eligibility->isDirectSellingEligible($member->fresh()));

        Order::create([
            'order_num' => 'T'.now()->format('ymd').'0101',
            'user_id' => $member->user_id,
            'subtotal' => 800,
            'total' => 800,
            'status' => 'delivered',
            'pay_status' => 'paid',
            'currency' => 'INR',
        ]);

        $this->assertTrue($eligibility->isDirectSellingEligible($member->fresh()));
    }

    public function test_ahmedabad_delivery_block_outside_area(): void
    {
        $user = $this->makeCustomer('C25070103', '9000000004', 'password');

        $this->actingAs($user)
            ->postJson('/api/v1/orders', [
                'items' => [['product_name' => 'Kit', 'price' => 200, 'quantity' => 1]],
                'city' => 'Mumbai',
                'pincode' => '400001',
                'shipping_address' => 'Andheri Mumbai 400001',
                'payment_method' => 'cod',
                'special_instruction' => 'Call on arrival',
            ])
            ->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_ahmedabad_order_gets_t_id_and_allows_area(): void
    {
        $user = $this->makeCustomer('C25070104', '9000000005', 'password');

        $response = $this->actingAs($user)
            ->postJson('/api/v1/orders', [
                'items' => [['product_name' => 'Kit', 'price' => 200, 'quantity' => 1]],
                'city' => 'Ahmedabad',
                'pincode' => '380001',
                'shipping_address' => 'Navrangpura Ahmedabad 380001',
                'payment_method' => 'cod',
                'special_instruction' => 'Call before delivery',
                'order_channel' => 'online',
            ])
            ->assertCreated()
            ->assertJsonPath('success', true);

        $orderNum = $response->json('data.order_number');
        $this->assertMatchesRegularExpression('/^T\d{6}\d{4}$/', (string) $orderNum);
    }

    public function test_offline_order_requires_remark_document_and_amount(): void
    {
        Storage::fake('public');
        $user = $this->makeCustomer('C25070105', '9000000006', 'password');

        $this->actingAs($user)
            ->postJson('/api/v1/orders', [
                'items' => [['product_name' => 'x', 'price' => 1, 'quantity' => 1]],
                'order_channel' => 'offline',
                'city' => 'Ahmedabad',
                'pincode' => '380001',
                'shipping_address' => 'Ahmedabad 380001',
                'payment_method' => 'cod',
            ])
            ->assertStatus(422);

        $file = UploadedFile::fake()->create('bill.pdf', 100, 'application/pdf');

        $this->actingAs($user)
            ->post('/api/v1/orders', [
                'items' => [['product_name' => 'x', 'price' => 1, 'quantity' => 1]],
                'order_channel' => 'offline',
                'offline_amount' => 550,
                'offline_remark' => 'Paid at office counter',
                'offline_document' => $file,
                'special_instruction' => 'Collect receipt',
                'city' => 'Ahmedabad',
                'pincode' => '380001',
                'shipping_address' => 'Ahmedabad 380001',
                'payment_method' => 'cod',
            ], ['Accept' => 'application/json'])
            ->assertCreated()
            ->assertJsonPath('data.order_channel', 'offline');
    }

    public function test_sponsor_can_place_joinee_after_min_purchase(): void
    {
        $sponsor = $this->makeMember('C25070110', '9000000010');
        $joinee = $this->makeMember('C25070111', '9000000011', $sponsor);

        $this->actingAs($sponsor->user)
            ->post(route('network.placement.store'), [
                'joinee_member_id' => $joinee->id,
                'new_parent_id' => $sponsor->id,
                'new_position' => Member::POSITION_LEFT,
            ])
            ->assertSessionHasErrors('joinee_member_id');

        Order::create([
            'order_num' => 'T'.now()->format('ymd').'0200',
            'user_id' => $joinee->user_id,
            'subtotal' => 200,
            'total' => 200,
            'status' => 'delivered',
            'pay_status' => 'paid',
            'currency' => 'INR',
        ]);

        $this->actingAs($sponsor->user)
            ->post(route('network.placement.store'), [
                'joinee_member_id' => $joinee->id,
                'new_parent_id' => $sponsor->id,
                'new_position' => Member::POSITION_LEFT,
                'reason' => 'Sponsor placement after ₹200',
            ])
            ->assertRedirect(route('network.placement'));

        $joinee->refresh();
        $this->assertSame($sponsor->id, $joinee->placement_parent_id);
        $this->assertSame(Member::POSITION_LEFT, $joinee->placement_position);
    }

    public function test_transactions_page_marks_ineligible_rows(): void
    {
        $member = $this->makeMember('C25070120', '9000000020');
        Order::create([
            'order_num' => 'T'.now()->format('ymd').'0300',
            'user_id' => $member->user_id,
            'subtotal' => 150,
            'total' => 150,
            'status' => 'pending',
            'pay_status' => 'pending',
            'currency' => 'INR',
        ]);

        $this->actingAs($member->user)
            ->get(route('transactions.index'))
            ->assertOk()
            ->assertSee('My Transactions')
            ->assertSee('Not Eligible', false);
    }

    private function makeCustomer(string $memberId, string $phone, string $password, bool $termsAccepted = true): User
    {
        return User::create([
            'name' => 'Customer '.$memberId,
            'email' => strtolower($memberId).'@example.com',
            'password' => bcrypt($password),
            'plain_password' => $password,
            'phone' => $phone,
            'role_id' => $this->customerRole->id,
            'status' => 'active',
            'mlm_member_id' => $memberId,
            'terms_accepted_at' => $termsAccepted ? now() : null,
        ]);
    }

    private function makeMember(string $memberId, string $phone, ?Member $sponsor = null): Member
    {
        $user = $this->makeCustomer($memberId, $phone, 'password');

        return Member::create([
            'user_id' => $user->id,
            'sponsor_member_id' => $sponsor?->id,
            'status' => Member::STATUS_ACTIVE,
            'joined_at' => now(),
            'kyc_status' => Member::KYC_APPROVED,
            'is_idle_slot' => false,
        ]);
    }
}
