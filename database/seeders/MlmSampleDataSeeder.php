<?php

namespace Database\Seeders;

use App\Models\Address;
use App\Models\Member;
use App\Models\MlmCalculationRule;
use App\Models\MlmIncomeLedger;
use App\Models\MlmMemberSequence;
use App\Models\MlmPayoutCycle;
use App\Models\MlmPayoutLine;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusHistory;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Role;
use App\Models\ShippingMethod;
use App\Models\User;
use App\Services\Mlm\MlmOrderIntegrationService;
use App\Services\Mlm\MlmPayoutService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class MlmSampleDataSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'admin@example.com')->first();
        if (! $admin) {
            $this->call(DatabaseSeeder::class);
            $admin = User::where('email', 'admin@example.com')->first();
        }

        $customerRole = Role::firstOrCreate(['name' => 'Customer'], ['guard_name' => 'web']);
        $rule = MlmCalculationRule::where('status', 'active')->first();
        if (! $rule) {
            $rule = MlmCalculationRule::create([
                'version' => 'v1.0',
                'name' => 'Direct Selling PV Formula v1',
                'high_pv_rate' => 13.5,
                'low_pv_rate' => 0.75,
                'pv_divisor' => 3000,
                'income_rate' => 0.2,
                'high_level_start' => 0,
                'high_level_end' => 7,
                'low_level_start' => 8,
                'low_level_end' => 19,
                'status' => 'active',
                'effective_from' => '2020-01-01 00:00:00',
                'created_by' => $admin->id,
            ]);
        } else {
            $rule->update([
                'name' => 'Direct Selling PV Formula v1',
                'effective_from' => '2020-01-01 00:00:00',
            ]);
        }

        $shipping = ShippingMethod::firstOrCreate(
            ['name' => 'Standard Delivery'],
            ['charge' => 50.00, 'min_free_order' => 500.00, 'status' => 'active']
        );

        $product = Product::where('status', 'active')->first();
        if (! $product) {
            $product = Product::create([
                'name' => 'Aaradhya Special Combo Pack',
                'slug' => 'aaradhya-special-combo-pack',
                'sku' => 'AGU001',
                'price' => 1000.00,
                'sale_price' => 800.00,
                'gst_percentage' => 5,
                'stock_qty' => 5000,
                'image' => 'images/tata-dal.jpg',
                'status' => 'active',
            ]);
        }

        $appKey = (string) config('app.key');

        // Locate and load client master data JSON
        $dataPath = database_path('data/mlm_client_data.json');
        if (! file_exists($dataPath)) {
            $fallbackPath = 'C:/Users/LENOVO/Downloads/Aaradhya Gruh Udyog - Master Data.xlsx';
            if (file_exists($fallbackPath)) {
                // If JSON not built yet, we could load it from fallback
            }
            throw new \RuntimeException("MLM Client Master Data file not found at: {$dataPath}");
        }

        $clientData = json_decode(file_get_contents($dataPath), true);
        $customers = $clientData['customers'] ?? [];
        $placements = $clientData['placements'] ?? [];
        $transactions = $clientData['transactions'] ?? [];

        // Clean existing MLM dummy members and orders safely
        \Illuminate\Support\Facades\Schema::disableForeignKeyConstraints();
        MlmPayoutLine::truncate();
        MlmPayoutCycle::truncate();
        MlmIncomeLedger::truncate();
        DB::table('mlm_calculation_audits')->truncate();
        DB::table('mlm_calculation_runs')->truncate();
        DB::table('cashback_action_histories')->truncate();
        DB::table('cashback_status_histories')->truncate();
        DB::table('cashback_adjustments')->truncate();
        DB::table('cashback_payout_batch_items')->truncate();
        DB::table('cashback_payout_batches')->truncate();
        DB::table('cashback_profit_pools')->truncate();
        DB::table('cashback_eligibilities')->truncate();
        DB::table('mlm_kyc_histories')->truncate();
        DB::table('mlm_placement_movements')->truncate();
        OrderItem::truncate();
        OrderStatusHistory::truncate();
        Payment::truncate();
        Order::truncate();
        Member::truncate();
        Address::truncate();
        User::where('role_id', $customerRole->id)->delete();
        \Illuminate\Support\Facades\Schema::enableForeignKeyConstraints();

        $defaultPassword = Hash::make('password');
        $userMap = [];
        $memberMap = [];

        DB::beginTransaction();

        // Pass 1: Insert all Users, Addresses, and Members from real client master data
        $idleMap = [
            'C25070004' => 'Idle - 2',
            'C25070005' => 'Idle - 3',
            'C25070006' => 'Idle - 4',
            'C25070007' => 'Idle - 5',
            'C25070008' => 'Idle - 6',
            'C25070009' => 'Idle - 7',
            'C25070010' => 'Idle - 8',
            'C25070011' => 'Idle - 9',
            'C25070012' => 'Idle - 10',
            'C25070013' => 'Idle - 11',
            'C25070014' => 'Idle - 12',
            'C25070015' => 'Idle - 13',
        ];

        foreach ($customers as $idx => $c) {
            $code = $c['code'];
            if ($code === 'C25070003') continue; // C25070003 was dummy M1; Nimesh C22020008 is Top Root M1

            $idleLabel = $idleMap[$code] ?? null;
            $isIdle = $idleLabel !== null;

            $name = $isIdle ? $idleLabel : (! empty($c['name']) ? $c['name'] : 'Customer '.$code);
            $phone = ! empty($c['mobile']) ? $c['mobile'] : '98000'.str_pad((string) ($idx + 1), 5, '0', STR_PAD_LEFT);
            $email = strtolower($code).'@kharchdaan.com';
            $regDate = Carbon::parse($c['reg_date'])->setTime(10, 0, 0);

            $user = User::create([
                'name' => $name,
                'email' => $email,
                'password' => $defaultPassword,
                'plain_password' => 'password',
                'phone' => $phone,
                'role_id' => $customerRole->id,
                'status' => $c['status'] === 'blocked' ? 'blocked' : 'active',
                'mlm_member_id' => $code,
                'created_at' => $regDate,
                'updated_at' => $regDate,
            ]);
            $userMap[$code] = $user;

            $nameParts = explode(' ', trim($name));
            $fName = array_shift($nameParts);
            $lName = ! empty($nameParts) ? implode(' ', $nameParts) : '';

            Address::create([
                'user_id' => $user->id,
                'type' => 'shipping',
                'fname' => $fName,
                'lname' => $lName,
                'address' => ! empty($c['address']) ? $c['address'] : (! empty($c['area']) ? $c['area'] : 'Ahmedabad'),
                'apt' => ! empty($c['area']) ? $c['area'] : null,
                'city' => ! empty($c['city']) ? $c['city'] : 'Ahmedabad',
                'state' => ! empty($c['state']) ? $c['state'] : 'Gujarat',
                'zip' => ! empty($c['pincode']) ? $c['pincode'] : '380001',
                'country' => 'India',
                'phone' => $phone,
                'default' => true,
                'created_at' => $regDate,
                'updated_at' => $regDate,
            ]);

            $pan = 'ABC'.strtoupper(substr(md5($code), 0, 6)).'Z';
            $aadhaar = '2'.str_pad((string) ($idx + 1), 11, '0', STR_PAD_LEFT);
            $bankAcc = '91'.str_pad((string) ($idx + 1), 10, '0', STR_PAD_LEFT);

            $member = Member::create([
                'user_id' => $user->id,
                'sponsor_member_id' => null,
                'placement_parent_id' => null,
                'placement_position' => null,
                'status' => $c['status'] === 'blocked' ? Member::STATUS_BLOCKED : Member::STATUS_ACTIVE,
                'is_idle_slot' => $isIdle,
                'idle_slot_label' => $idleLabel,
                'joined_at' => $regDate,
                'created_by' => $admin->id,
                'kyc_status' => Member::KYC_APPROVED,
                'pan_number' => $pan,
                'pan_hash' => hash_hmac('sha256', $pan, $appKey),
                'aadhaar_reference' => $aadhaar,
                'aadhaar_hash' => hash_hmac('sha256', $aadhaar, $appKey),
                'bank_account_holder_name' => $name,
                'bank_account_number' => $bankAcc,
                'bank_account_hash' => hash_hmac('sha256', $bankAcc, $appKey),
                'ifsc_code' => 'SBIN0004567',
                'bank_name' => 'State Bank of India',
                'bank_branch' => ! empty($c['area']) ? $c['area'] : 'Ahmedabad',
                'kyc_verified_by' => $admin->id,
                'kyc_verified_at' => $regDate,
                'created_at' => $regDate,
                'updated_at' => $regDate,
            ]);
            $memberMap[$code] = $member;
        }

        // Pass 2: Link direct sponsors and placement hierarchy slots
        foreach ($customers as $c) {
            $code = $c['code'];
            $member = $memberMap[$code] ?? null;
            if (! $member) {
                continue;
            }

            $refCode = $c['referral_id'] ?? '';
            $sponsorMember = $memberMap[$refCode] ?? null;

            $placementInfo = $placements[$code] ?? null;
            $placementParent = $placementInfo ? ($memberMap[$placementInfo['upline']] ?? null) : null;
            $placementPosition = $placementInfo ? $placementInfo['position'] : null;

            $updates = [];
            if ($sponsorMember && $sponsorMember->id !== $member->id) {
                $updates['sponsor_member_id'] = $sponsorMember->id;
                $updates['sponsor_name_snapshot'] = $sponsorMember->user->name;
                $updates['sponsor_relationship_status'] = 'active';
                $updates['sponsor_assigned_at'] = $member->joined_at;
            }

            if ($code === 'C22020008') {
                $updates['placement_parent_id'] = null;
                $updates['placement_position'] = null;
            } elseif ($placementParent && $placementParent->id !== $member->id) {
                $updates['placement_parent_id'] = $placementParent->id;
                $updates['placement_position'] = $placementPosition;
            }

            if (! empty($updates)) {
                DB::table('mlm_members')->where('id', $member->id)->update($updates);
            }
        }

        DB::commit();

        // Update member sequence
        MlmMemberSequence::updateOrCreate(
            ['period' => now()->format('ym')],
            ['next_number' => 800]
        );

        // Seed realistic orders for active transacting members
        $orderService = app(MlmOrderIntegrationService::class);
        $seenMembers = [];
        $ordersProcessed = 0;

        foreach ($transactions as $tx) {
            if ($ordersProcessed >= 35) {
                break;
            }
            $custCode = $tx['customer_code'];
            if (isset($seenMembers[$custCode])) {
                continue;
            }

            $member = $memberMap[$custCode] ?? null;
            if (! $member || $member->status !== Member::STATUS_ACTIVE) {
                continue;
            }

            // Must have a placement parent to distribute upline commissions
            $hasParent = DB::table('mlm_members')->where('id', $member->id)->whereNotNull('placement_parent_id')->exists();
            if (! $hasParent) {
                continue;
            }

            $seenMembers[$custCode] = true;
            $amount = (float) $tx['amount'];
            if ($amount < 250) {
                $amount = 600.0;
            }
            $subtotal = $amount;
            $gst = round($subtotal * 0.05, 2);
            $total = $subtotal + $gst;
            $orderDate = Carbon::parse($tx['date'])->setTime(rand(10, 18), rand(0, 59));

            $order = Order::create([
                'order_num' => 'ORD-TXN-'.$tx['tx_id'],
                'user_id' => $member->user_id,
                'subtotal' => $subtotal,
                'discount' => 0.00,
                'gst_amt' => $gst,
                'ship_charge' => 0.00,
                'total' => $total,
                'ship_id' => $shipping->id,
                'status' => 'delivered',
                'pay_status' => 'paid',
                'created_at' => $orderDate,
                'updated_at' => $orderDate,
            ]);

            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $product->id,
                'qty' => max(1, (int) ($tx['qty'] ?? 1)),
                'price' => $subtotal / max(1, (int) ($tx['qty'] ?? 1)),
                'gst_pct' => 5,
            ]);

            OrderStatusHistory::create([
                'order_id' => $order->id,
                'status' => 'delivered',
                'note' => 'Delivered to customer',
                'created_at' => $orderDate,
            ]);

            Payment::create([
                'order_id' => $order->id,
                'amount' => $total,
                'method' => 'online',
                'status' => 'paid',
                'txn_id' => 'TXN-AGU-'.$tx['tx_id'],
                'created_at' => $orderDate,
            ]);

            $orderService->process($order);
            $ordersProcessed++;
        }

        // Generate weekly payout settlement cycles
        $payoutService = app(MlmPayoutService::class);
        try {
            $cycle = $payoutService->createWeeklyCycle(
                now()->startOfWeek(),
                $admin,
                'Aaradhya Gruh Udyog Weekly Settlement Cycle'
            );
            if ($cycle && $cycle->status === MlmPayoutCycle::STATUS_PENDING_APPROVAL) {
                $cycle = $payoutService->approve($cycle, $admin);
            }
        } catch (\Throwable $e) {
            // Safe fallback
        }
    }
}
