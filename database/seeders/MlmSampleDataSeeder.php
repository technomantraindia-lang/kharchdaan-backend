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
use App\Services\Mlm\MlmCalculationService;
use App\Services\Mlm\MlmOrderIntegrationService;
use App\Services\Mlm\MlmPayoutService;
use App\Support\MlmDecimal;
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
                'effective_from' => now()->subYears(1)->startOfDay(),
                'created_by' => $admin->id,
            ]);
        } else {
            $rule->update([
                'name' => 'Direct Selling PV Formula v1',
                'effective_from' => now()->subYears(1)->startOfDay(),
            ]);
        }

        $shipping = ShippingMethod::firstOrCreate(
            ['name' => 'Standard Delivery'],
            ['charge' => 50.00, 'min_free_order' => 500.00, 'status' => 'active']
        );

        $products = Product::where('status', 'active')->get();
        if ($products->isEmpty()) {
            $products = collect([
                Product::create([
                    'name' => 'Direct Selling Starter Package',
                    'slug' => 'direct-selling-starter-package',
                    'sku' => 'KD001',
                    'price' => 1500.00,
                    'sale_price' => 1000.00,
                    'gst_percentage' => 5,
                    'stock_qty' => 500,
                    'image' => 'images/tata-dal.jpg',
                    'status' => 'active',
                ]),
                Product::create([
                    'name' => 'KharchDaan Wellness Pack',
                    'slug' => 'kharchdaan-wellness-pack',
                    'sku' => 'KD002',
                    'price' => 2000.00,
                    'sale_price' => 1800.00,
                    'gst_percentage' => 5,
                    'stock_qty' => 500,
                    'image' => 'images/health-wellness-ayurveda.jpg',
                    'status' => 'active',
                ]),
            ]);
        }

        $appKey = (string) config('app.key');
        $period = now()->format('ym');

        $firstNames = [
            'Aarav', 'Vihaan', 'Vivaan', 'Ananya', 'Diya', 'Advik', 'Kabir', 'Aryan', 'Ishaan', 'Dhruv',
            'Saanvi', 'Mira', 'Riya', 'Avani', 'Aditi', 'Prisha', 'Anika', 'Tara', 'Navya', 'Pooja',
            'Rahul', 'Rohit', 'Amit', 'Sunil', 'Vikas', 'Deepak', 'Suresh', 'Manish', 'Rajesh', 'Sanjay',
            'Priya', 'Neha', 'Sneha', 'Swati', 'Pooja', 'Anjali', 'Kavita', 'Shreya', 'Divya', 'Payal',
            'Gaurav', 'Nitin', 'Alok', 'Saurabh', 'Ashish', 'Pankaj', 'Abhishek', 'Sachin', 'Mohit', 'Kunal',
            'Kiran', 'Megha', 'Ritu', 'Simran', 'Komal', 'Sonia', 'Barkha', 'Tanvi', 'Isha', 'Bhavna',
            'Arun', 'Varun', 'Tarun', 'Harish', 'Lalit', 'Manoj', 'Vijay', 'Ajay', 'Kamal', 'Rakesh',
            'Shweta', 'Monika', 'Preeti', 'Jyoti', 'Kavita', 'Sangeeta', 'Sunita', 'Rekha', 'Geeta', 'Usha',
            'Naveen', 'Pradeep', 'Dharmendra', 'Ramesh', 'Mukesh', 'Mahesh', 'Girish', 'Naresh', 'Yogesh', 'Umesh',
            'Chirag', 'Tushar', 'Mayank', 'Harsh', 'Yash', 'Dev', 'Karan', 'Rohan', 'Sameer', 'Vikram'
        ];

        $lastNames = [
            'Sharma', 'Verma', 'Gupta', 'Patel', 'Singh', 'Kumar', 'Joshi', 'Mehta', 'Shah', 'Nair',
            'Chopra', 'Malhotra', 'Bhatia', 'Reddy', 'Rao', 'Iyer', 'Agarwal', 'Bansal', 'Mittal', 'Garg',
            'Deshmukh', 'Kulkarni', 'Patil', 'Pawar', 'Shinde', 'Jadhav', 'More', 'Chavan', 'Kadam', 'Sawant',
            'Das', 'Banerjee', 'Chatterjee', 'Mukherjee', 'Dutta', 'Ghosh', 'Sen', 'Roy', 'Bose', 'Saha',
            'Pandey', 'Mishra', 'Tiwari', 'Dubey', 'Shukla', 'Tripathi', 'Chaubey', 'Dwivedi', 'Upadhyay', 'Pathak'
        ];

        $banks = [
            ['name' => 'HDFC Bank', 'ifsc' => 'HDFC0001234', 'branch' => 'Connaught Place, Delhi'],
            ['name' => 'State Bank of India', 'ifsc' => 'SBIN0004567', 'branch' => 'Bandra Kurla, Mumbai'],
            ['name' => 'ICICI Bank', 'ifsc' => 'ICIC0009876', 'branch' => 'MG Road, Bangalore'],
            ['name' => 'Axis Bank', 'ifsc' => 'UTIB0002345', 'branch' => 'Sector 18, Noida'],
            ['name' => 'Kotak Mahindra Bank', 'ifsc' => 'KKBK0003456', 'branch' => 'C-Scheme, Jaipur'],
            ['name' => 'Punjab National Bank', 'ifsc' => 'PUNB0007890', 'branch' => 'Civil Lines, Ludhiana'],
        ];

        $positions = ['left', 'middle', 'right'];

        // Clean existing members safely to avoid duplicate hash collisions on re-seed
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
        User::where('email', 'like', 'member%@example.com')->delete();
        \Illuminate\Support\Facades\Schema::enableForeignKeyConstraints();

        $createdMembers = [];
        $totalMembers = 100;

        // Ensure primary customer exists
        $rootUser = User::where('email', 'customer@example.com')->first();
        if (! $rootUser) {
            $rootUser = User::create([
                'name' => 'John Doe (Root Leader)',
                'email' => 'customer@example.com',
                'password' => Hash::make('password'),
                'phone' => '9800000001',
                'role_id' => $customerRole->id,
                'status' => 'active',
                'mlm_member_id' => 'C'.$period.'0001',
            ]);
        } else {
            $rootUser->update([
                'name' => 'John Doe (Root Leader)',
                'phone' => '9800000001',
                'mlm_member_id' => 'C'.$period.'0001',
            ]);
        }

        // 1. Create Root Member
        $pan1 = 'ABCDE1001F';
        $aadhaar1 = '100000000001';
        $bankAcc1 = '910000000001';
        $rootMember = Member::create([
            'user_id' => $rootUser->id,
            'sponsor_member_id' => null,
            'placement_parent_id' => null,
            'placement_position' => null,
            'status' => Member::STATUS_ACTIVE,
            'joined_at' => now()->subDays(60),
            'created_by' => $admin->id,
            'sponsor_name_snapshot' => null,
            'sponsor_relationship_status' => null,
            'sponsor_assigned_at' => null,
            'kyc_status' => Member::KYC_APPROVED,
            'pan_number' => $pan1,
            'pan_hash' => hash_hmac('sha256', $pan1, $appKey),
            'aadhaar_reference' => $aadhaar1,
            'aadhaar_hash' => hash_hmac('sha256', $aadhaar1, $appKey),
            'bank_account_holder_name' => $rootUser->name,
            'bank_account_number' => $bankAcc1,
            'bank_account_hash' => hash_hmac('sha256', $bankAcc1, $appKey),
            'ifsc_code' => 'HDFC0001234',
            'bank_name' => 'HDFC Bank',
            'bank_branch' => 'Connaught Place, Delhi',
            'kyc_verified_by' => $admin->id,
            'kyc_verified_at' => now()->subDays(55),
        ]);
        $createdMembers[1] = $rootMember;

        // 2. Create Downline Members 2 through 100 in 1:3 Ternary Tree
        $placementParentQueue = [1];
        $currentParentIdx = 0;
        $currentPositionIdx = 0;

        for ($i = 2; $i <= $totalMembers; $i++) {
            $parentId = $placementParentQueue[$currentParentIdx];
            $position = $positions[$currentPositionIdx];

            // Advance position pointer
            $currentPositionIdx++;
            if ($currentPositionIdx >= 3) {
                $currentPositionIdx = 0;
                $currentParentIdx++;
            }
            $placementParentQueue[] = $i;

            // Determine sponsor: 50% direct sponsor by parent, 30% by root, 20% by an upline
            $sponsorId = match ($i % 5) {
                0, 1 => 1, // Root
                2, 3 => $parentId, // Direct parent
                default => max(1, $parentId - 1), // Upline
            };

            $fName = $firstNames[($i - 1) % count($firstNames)];
            $lName = $lastNames[($i - 1) % count($lastNames)];
            $name = $fName . ' ' . $lName;
            $email = 'member' . $i . '@example.com';
            $phone = '98000' . str_pad((string) $i, 5, '0', STR_PAD_LEFT);
            $memberCode = 'C' . $period . str_pad((string) $i, 4, '0', STR_PAD_LEFT);

            $user = User::create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make('password'),
                'phone' => $phone,
                'role_id' => $customerRole->id,
                'status' => $i === 97 ? 'inactive' : ($i === 98 ? 'blocked' : 'active'),
                'mlm_member_id' => $memberCode,
            ]);

            Address::create([
                'user_id' => $user->id,
                'type' => 'shipping',
                'fname' => $fName,
                'lname' => $lName,
                'address' => ($i * 12) . ', Main Street, Sector ' . ($i % 20 + 1),
                'city' => ['Delhi', 'Mumbai', 'Bangalore', 'Jaipur', 'Lucknow', 'Pune', 'Kolkata', 'Hyderabad'][$i % 8],
                'state' => ['Delhi', 'Maharashtra', 'Karnataka', 'Rajasthan', 'Uttar Pradesh', 'Maharashtra', 'West Bengal', 'Telangana'][$i % 8],
                'zip' => str_pad((string) (110000 + $i), 6, '0', STR_PAD_LEFT),
                'country' => 'India',
                'phone' => $phone,
                'default' => true,
            ]);

            // KYC Status variation
            $kycStatus = match (true) {
                $i % 15 === 0 => Member::KYC_REJECTED,
                $i % 10 === 0 => Member::KYC_PENDING,
                $i % 7 === 0 => Member::KYC_UNDER_REVIEW,
                default => Member::KYC_APPROVED,
            };

            $pan = 'ABCDE' . str_pad((string) (1000 + $i), 4, '0', STR_PAD_LEFT) . chr(65 + ($i % 26));
            $aadhaar = '1' . str_pad((string) $i, 11, '0', STR_PAD_LEFT);
            $bankAcc = '91' . str_pad((string) $i, 10, '0', STR_PAD_LEFT);
            $bankInfo = $banks[$i % count($banks)];

            $sponsorMember = $createdMembers[$sponsorId] ?? $rootMember;

            $status = match (true) {
                $i === 97 => Member::STATUS_INACTIVE,
                $i === 98 => Member::STATUS_BLOCKED,
                $i >= 95 => Member::STATUS_PENDING,
                default => Member::STATUS_ACTIVE,
            };

            $member = Member::create([
                'user_id' => $user->id,
                'sponsor_member_id' => $sponsorMember->id,
                'placement_parent_id' => $parentId,
                'placement_position' => $position,
                'status' => $status,
                'joined_at' => now()->subDays(max(1, 60 - (int) ($i / 2))),
                'created_by' => $admin->id,
                'sponsor_name_snapshot' => $sponsorMember->user->name,
                'sponsor_relationship_status' => 'active',
                'sponsor_assigned_at' => now()->subDays(max(1, 60 - (int) ($i / 2))),
                'kyc_status' => $kycStatus,
                'pan_number' => $pan,
                'pan_hash' => hash_hmac('sha256', $pan, $appKey),
                'aadhaar_reference' => $aadhaar,
                'aadhaar_hash' => hash_hmac('sha256', $aadhaar, $appKey),
                'bank_account_holder_name' => $name,
                'bank_account_number' => $bankAcc,
                'bank_account_hash' => hash_hmac('sha256', $bankAcc, $appKey),
                'ifsc_code' => $bankInfo['ifsc'],
                'bank_name' => $bankInfo['name'],
                'bank_branch' => $bankInfo['branch'],
                'kyc_rejection_reason' => $kycStatus === Member::KYC_REJECTED ? 'Bank passbook copy is blurred and illegible' : null,
                'kyc_verified_by' => $kycStatus === Member::KYC_APPROVED ? $admin->id : null,
                'kyc_verified_at' => $kycStatus === Member::KYC_APPROVED ? now()->subDays(max(1, 55 - (int) ($i / 2))) : null,
            ]);

            $createdMembers[$i] = $member;
        }

        // Update sequence so new additions start at 101
        MlmMemberSequence::updateOrCreate(
            ['period' => $period],
            ['next_number' => 101]
        );

        // 3. Create Realistic Orders across Downline Members and Trigger MLM Calculations
        $orderService = app(MlmOrderIntegrationService::class);

        // Select 32 distinct active members across level 1 to level 4
        $orderingMemberIds = [
            1, 2, 3, 4, 5, 8, 12, 14, 18, 22, 27, 31, 35, 40, 45, 50, 55, 60, 65, 70, 75, 80, 85, 90, 92, 94, 15, 20, 25, 30, 36, 42
        ];

        $orderAmounts = [
            500.00, 750.00, 1200.00, 1800.00, 2500.00, 3000.00, 4500.00, 6000.00, 8500.00, 10000.00, 12500.00, 15000.00
        ];

        // 4 time buckets: 3 weeks ago, 2 weeks ago, 1 week ago, current week
        $weekDates = [
            0 => now()->subWeeks(3)->startOfWeek()->addDays(2)->setTime(11, 30),
            1 => now()->subWeeks(2)->startOfWeek()->addDays(2)->setTime(14, 15),
            2 => now()->subWeeks(1)->startOfWeek()->addDays(2)->setTime(16, 45),
            3 => now()->startOfWeek()->addDays(1)->setTime(10, 0),
        ];

        foreach ($orderingMemberIds as $idx => $mId) {
            $member = $createdMembers[$mId] ?? null;
            if (! $member || $member->status !== Member::STATUS_ACTIVE) {
                continue;
            }

            $weekIndex = $idx % 4;
            $orderDate = $weekDates[$weekIndex]->copy()->addHours($idx % 5);
            $subtotal = $orderAmounts[$idx % count($orderAmounts)];
            $gst = round($subtotal * 0.05, 2);
            $total = $subtotal + $gst;

            $order = Order::create([
                'order_num' => 'ORD-MLM-' . (10000 + $idx + 1),
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
                'product_id' => $products->first()->id,
                'qty' => (int) max(1, round($subtotal / 150)),
                'price' => 150.00,
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
                'txn_id' => 'TXN-MLM-' . (50000 + $idx + 1),
                'created_at' => $orderDate,
            ]);

            // Calculate MLM PV and commission distribution up the tree
            $orderService->process($order);
        }

        // 4. Generate Weekly Payout Cycles
        $payoutService = app(MlmPayoutService::class);

        // Cycle 1: 3 weeks ago (Paid cycle)
        try {
            $cycleDate1 = now()->subWeeks(3)->startOfWeek();
            $cycle1 = $payoutService->createWeeklyCycle($cycleDate1, $admin, 'Weekly MLM Settlement Cycle 1');
            if ($cycle1 && $cycle1->status === MlmPayoutCycle::STATUS_PENDING_APPROVAL) {
                $cycle1 = $payoutService->approve($cycle1, $admin);
                $cycle1 = $payoutService->startProcessing($cycle1, $admin);
                $payoutService->markPaid($cycle1, $admin, 'BATCH-PAY-2026-W36', now()->subWeeks(3)->endOfWeek(), 'All bank NEFT transfers settled successfully.');
            }
        } catch (\Throwable $e) {
            // Safe fallback
        }

        // Cycle 2: 2 weeks ago (Approved cycle)
        try {
            $cycleDate2 = now()->subWeeks(2)->startOfWeek();
            $cycle2 = $payoutService->createWeeklyCycle($cycleDate2, $admin, 'Weekly MLM Settlement Cycle 2');
            if ($cycle2 && $cycle2->status === MlmPayoutCycle::STATUS_PENDING_APPROVAL) {
                $payoutService->approve($cycle2, $admin);
            }
        } catch (\Throwable $e) {
            // Safe fallback
        }

        // Cycle 3: 1 week ago (Pending Approval cycle)
        try {
            $cycleDate3 = now()->subWeeks(1)->startOfWeek();
            $payoutService->createWeeklyCycle($cycleDate3, $admin, 'Weekly MLM Settlement Cycle 3 (Pending Approval)');
        } catch (\Throwable $e) {
            // Safe fallback
        }
    }
}
