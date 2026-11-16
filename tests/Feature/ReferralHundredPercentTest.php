<?php

namespace Tests\Feature;

use App\Services\Mlm\PurchaseEligibilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReferralHundredPercentTest extends TestCase
{
    use RefreshDatabase;

    public function test_referral_rate_defaults_to_one_hundred_percent(): void
    {
        config(['mlm.referral_rate' => '1.00']);
        $service = app(PurchaseEligibilityService::class);

        $this->assertSame('1.0000', $service->referralRate());
        // 100% of 831 -> round up to 840
        $this->assertSame('840.00', $service->referralIncomeFromAmount('831'));
        $this->assertSame('0.000000000000000000', $service->referralIncomeFromAmount('199.99'));
        $this->assertSame('200.00', $service->referralIncomeFromAmount('200'));
    }
}
