@extends('frontend.layouts.app')

@section('title', 'Terms & Conditions')

@push('styles')
<style>
    .terms-box {
        background: linear-gradient(180deg, #fff7ed, #fff);
        border: 2px solid #fdba74;
        border-radius: 1rem;
        padding: 1.25rem 1.5rem;
        max-height: 55vh;
        overflow-y: auto;
        color: #1f2937;
    }
    .terms-section {
        padding: 0.85rem 0;
        border-bottom: 1px solid #fed7aa;
    }
    .terms-section:last-child { border-bottom: 0; }
    .terms-section h3 {
        display: flex;
        align-items: center;
        gap: 0.6rem;
        font-size: 1.05rem;
        font-weight: 800;
        color: #9a3412;
        margin: 0 0 0.4rem;
    }
    .terms-num {
        width: 1.5rem;
        height: 1.5rem;
        border-radius: 999px;
        background: #ea580c;
        color: #fff;
        font-size: 0.75rem;
        font-weight: 800;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    .terms-section p {
        margin: 0;
        padding-left: 2.1rem;
        font-size: 1rem;
        line-height: 1.65;
    }
</style>
@endpush

@section('content')
<div class="row justify-content-center my-4">
    <div class="col-lg-9">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4 p-md-5">
                <h1 class="fw-bold mb-2" style="font-size: 2rem; color: #ea580c;">Terms &amp; Conditions</h1>
                <p class="text-muted mb-4" style="font-size: 1.1rem;">
                    Please read carefully. On first login you must accept these terms before using your account.
                </p>

                <div class="terms-box mb-4">
                    <div class="terms-section">
                        <h3><span class="terms-num">1</span> Account &amp; Membership</h3>
                        <p>One mobile number maps to one Customer ID. Keep your ID, mobile, and password secure. You are responsible for all activity on your account.</p>
                    </div>
                    <div class="terms-section">
                        <h3><span class="terms-num">2</span> Direct Selling Network</h3>
                        <p>Sponsor and 1:3 placement follow KharchDaan rules. Placement may be arranged by admin/sponsor as per policy. Benefits follow published Direct Selling criteria, including monthly purchase gates.</p>
                    </div>
                    <div class="terms-section">
                        <h3><span class="terms-num">3</span> Cashback &amp; Referral</h3>
                        <p>CashBack and Referral Income depend on Sanstha's profit and system criteria. Referral Income is 100% of qualifying purchases (rounded up to the next ₹10). Minimum ₹200 transaction rules apply. Amounts shown as pending are not guaranteed until marked Paid by admin after profit availability.</p>
                    </div>
                    <div class="terms-section">
                        <h3><span class="terms-num">4</span> Orders &amp; Delivery</h3>
                        <p>Orders are accepted in the Ahmedabad service area only. Status updates will appear on your order. Cancellation follows company process.</p>
                    </div>
                    <div class="terms-section">
                        <h3><span class="terms-num">5</span> KYC &amp; Payouts</h3>
                        <p>Aadhaar, PAN, and bank details with cancelled cheque may be required before payouts. Incorrect or duplicate KYC may delay or block settlements.</p>
                    </div>
                    <div class="terms-section">
                        <h3><span class="terms-num">6</span> Acceptance</h3>
                        <p>By accepting these Terms &amp; Conditions, you confirm that you have read them in full and agree to continue shopping and using Direct Selling features under these rules.</p>
                    </div>
                </div>

                @if($alreadyAccepted)
                    <div class="alert alert-success">You have already accepted the Terms &amp; Conditions.</div>
                    <a href="{{ route('account') }}" class="btn btn-primary">Go to My Account</a>
                @else
                    <form method="POST" action="{{ route('terms.accept') }}">
                        @csrf
                        <div class="form-check mb-4">
                            <input class="form-check-input @error('accept_terms') is-invalid @enderror" type="checkbox" value="1" id="accept_terms" name="accept_terms" required style="width:1.25rem;height:1.25rem;">
                            <label class="form-check-label fw-bold ms-2" for="accept_terms" style="font-size:1.15rem;">
                                I have read and accept the Terms &amp; Conditions
                            </label>
                            @error('accept_terms')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <button type="submit" class="btn btn-primary btn-lg fw-bold px-4">Accept &amp; Continue</button>
                    </form>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
