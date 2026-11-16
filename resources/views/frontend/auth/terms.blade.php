@extends('frontend.layouts.app')

@section('title', 'Terms & Conditions')

@push('styles')
<style>
    .terms-box {
        background: #fff7ed;
        border: 2px solid #fdba74;
        border-radius: 1rem;
        padding: 1.5rem;
        max-height: 55vh;
        overflow-y: auto;
        font-size: 1.15rem;
        line-height: 1.7;
        color: #1f2937;
    }
    .terms-box h3 { font-size: 1.35rem; font-weight: 800; color: #9a3412; margin-top: 1rem; }
    .terms-box p, .terms-box li { font-size: 1.1rem; }
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
                    <h3>1. Account &amp; Membership</h3>
                    <p>You are responsible for keeping your Customer ID, mobile number, and password secure. One mobile number is allowed for one customer account only.</p>

                    <h3>2. Direct Selling Network</h3>
                    <p>Sponsor and placement relationships are managed as per KharchDaan Direct Selling rules (1:3 matrix). Placement may be arranged by admin/sponsor as per policy. Commission and PV benefits follow published Direct Selling criteria.</p>

                    <h3>3. Cashback &amp; Referral Benefits</h3>
                    <p>CashBack and Referral Income depend on Sanstha's profit and system criteria (client requirement). Eligibility includes minimum ₹200 transaction rules and other published criteria. Benefits shown as pending are not guaranteed until marked Paid after profit availability and admin settlement.</p>

                    <h3>4. Orders &amp; Delivery</h3>
                    <p>Orders are accepted within the serviceable delivery area (primarily Ahmedabad and surrounding pincodes). Delivery status will be updated on your order. Cancellation follows company process.</p>

                    <h3>5. KYC &amp; Payouts</h3>
                    <p>Aadhaar, PAN, and bank details with cancelled cheque may be required for payouts. Incorrect or duplicate KYC may delay or block settlements.</p>

                    <h3>6. Acceptance</h3>
                    <p>By ticking the box below, you confirm that you have read and agree to these Terms &amp; Conditions in full.</p>
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
