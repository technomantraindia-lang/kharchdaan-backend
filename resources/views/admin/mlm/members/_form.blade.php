@php($editing = isset($member) && $member?->exists)

<div class="col-span-full">
    <div class="p-4 rounded-xl bg-slate-50 border border-slate-200/80 flex items-center justify-between">
        <div class="flex items-center gap-2">
            <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Member ID:</span>
            @if($editing)
                <span class="font-mono font-bold text-blue-600 text-sm bg-blue-50 px-2 py-0.5 rounded border border-blue-200">{{ $member->customer_id }}</span>
                <span class="text-xs text-slate-400 ms-2">Member IDs are immutable.</span>
            @else
                <span class="text-xs text-slate-500 font-medium">Auto-generated in format <code class="font-mono text-blue-600 bg-blue-50 px-1 py-0.5 rounded">CYYMM0001</code> upon creation.</span>
            @endif
        </div>
        @if($editing && $member->status)
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $member->status === 'active' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-700 border border-slate-200' }}">
                <span class="w-1.5 h-1.5 rounded-full {{ $member->status === 'active' ? 'bg-emerald-500' : 'bg-slate-400' }} mr-1.5"></span>
                {{ ucfirst($member->status) }}
            </span>
        @endif
    </div>
</div>

@if(!$editing)
    <div class="col-span-full">
        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Attach to Existing Customer Account</label>
        <select name="user_id" class="w-full px-3 py-2 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 outline-none transition @error('user_id') border-rose-400 bg-rose-50/30 @enderror">
            <option value="">Create a new Customer Account automatically</option>
            @foreach($existingCustomers as $customer)
                <option value="{{ $customer->id }}" @selected(old('user_id') == $customer->id)>{{ $customer->name }} — {{ $customer->email }}</option>
            @endforeach
        </select>
        <div class="text-[11px] text-slate-500 mt-1">Select an existing unassigned customer account to link, or leave blank to create a new user.</div>
        @error('user_id')<div class="text-rose-600 text-xs mt-1">{{ $message }}</div>@enderror
    </div>
@endif

<div class="col-span-full pt-2">
    <h3 class="text-sm font-bold text-slate-900 pb-2 border-b border-slate-200/80 flex items-center gap-2">
        <i class="fas fa-user text-blue-600 text-xs"></i> Personal Information
    </h3>
</div>

<div class="sm:col-span-1">
    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Full Name <span class="text-rose-500">*</span></label>
    <input type="text" name="name" class="w-full px-3 py-2 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 outline-none transition @error('name') border-rose-400 bg-rose-50/30 @enderror" value="{{ old('name', $member?->user?->name) }}" placeholder="e.g. Rahul Sharma" required>
    @error('name')<div class="text-rose-600 text-xs mt-1">{{ $message }}</div>@enderror
</div>

<div class="sm:col-span-1">
    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Mobile Number <span class="text-rose-500">*</span></label>
    <input type="text" name="mobile" class="w-full px-3 py-2 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 outline-none transition @error('mobile') border-rose-400 bg-rose-50/30 @enderror" value="{{ old('mobile', $member?->mobile) }}" placeholder="10-digit mobile" required>
    @error('mobile')<div class="text-rose-600 text-xs mt-1">{{ $message }}</div>@enderror
</div>

<div class="sm:col-span-1">
    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Email Address <span class="text-rose-500">*</span></label>
    <input type="email" name="email" class="w-full px-3 py-2 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 outline-none transition @error('email') border-rose-400 bg-rose-50/30 @enderror" value="{{ old('email', $member?->user?->email) }}" placeholder="name@example.com" required>
    @error('email')<div class="text-rose-600 text-xs mt-1">{{ $message }}</div>@enderror
</div>

@if(!$editing)
    <div class="sm:col-span-1">
        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Joining Date</label>
        <input type="date" name="joining_date" class="w-full px-3 py-2 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 outline-none transition @error('joining_date') border-rose-400 bg-rose-50/30 @enderror" value="{{ old('joining_date', now()->format('Y-m-d')) }}" max="{{ now()->format('Y-m-d') }}" required>
        @error('joining_date')<div class="text-rose-600 text-xs mt-1">{{ $message }}</div>@enderror
    </div>
@else
    <div class="sm:col-span-1">
        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Joining Date</label>
        <input type="text" class="w-full px-3 py-2 text-xs bg-slate-100 border border-slate-200 rounded-lg text-slate-600 cursor-not-allowed" value="{{ $member->joined_at?->format('M d, Y') }}" readonly>
    </div>
@endif

<div class="sm:col-span-1">
    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Account Status <span class="text-rose-500">*</span></label>
    <select name="status" class="w-full px-3 py-2 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 outline-none transition @error('status') border-rose-400 bg-rose-50/30 @enderror" required>
        @foreach(\App\Models\Member::STATUSES as $status)
            <option value="{{ $status }}" @selected(old('status', $member?->status ?? 'pending') === $status)>{{ ucfirst($status) }}</option>
        @endforeach
    </select>
    @error('status')<div class="text-rose-600 text-xs mt-1">{{ $message }}</div>@enderror
</div>

<div class="sm:col-span-1">
    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Profile Photo</label>
    <input type="file" name="profile_photo" class="w-full px-3 py-1.5 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 outline-none transition @error('profile_photo') border-rose-400 bg-rose-50/30 @enderror" accept="image/jpeg,image/png">
    @error('profile_photo')<div class="text-rose-600 text-xs mt-1">{{ $message }}</div>@enderror
</div>

{{-- Sponsor Information --}}
<div class="col-span-full pt-4">
    <h3 class="text-sm font-bold text-slate-900 pb-2 border-b border-slate-200/80 flex items-center gap-2">
        <i class="fas fa-user-friends text-emerald-600 text-xs"></i> Sponsor Information
    </h3>
</div>

@if(!$editing)
    <div class="sm:col-span-1">
        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Sponsor Member ID</label>
        <input type="text" name="sponsor_customer_id" id="sponsorCustomerId" class="w-full px-3 py-2 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 outline-none transition font-mono @error('sponsor_customer_id') border-rose-400 bg-rose-50/30 @enderror" value="{{ old('sponsor_customer_id') }}" list="sponsor-options" placeholder="e.g. C26090001">
        <datalist id="sponsor-options">
            @foreach($sponsors as $sponsor)
                <option value="{{ $sponsor->customer_id }}" data-name="{{ $sponsor->user?->name }}">{{ $sponsor->user?->name }}</option>
            @endforeach
        </datalist>
        <div id="sponsorPreview" class="text-[11px] text-slate-500 mt-1">Leave blank only if creating a root leader.</div>
        @error('sponsor_customer_id')<div class="text-rose-600 text-xs mt-1">{{ $message }}</div>@enderror
    </div>
    <div class="sm:col-span-1 flex items-center pt-5">
        <label class="inline-flex items-center gap-2 text-xs font-medium text-slate-700 cursor-pointer">
            <input type="checkbox" name="root_member" value="1" id="rootMember" @checked(old('root_member')) class="w-4 h-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500">
            <span>Explicitly create as root member (No sponsor)</span>
        </label>
    </div>
@else
    <div class="sm:col-span-1">
        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Sponsor Member ID</label>
        <input type="text" class="w-full px-3 py-2 text-xs bg-slate-100 border border-slate-200 rounded-lg text-slate-600 font-mono" value="{{ $member->sponsor?->customer_id ?? 'Root / None' }}" readonly>
    </div>
    <div class="sm:col-span-1">
        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Sponsor Name</label>
        <input type="text" class="w-full px-3 py-2 text-xs bg-slate-100 border border-slate-200 rounded-lg text-slate-600" value="{{ $member->sponsor_name_snapshot ?? $member->sponsor?->user?->name ?? '-' }}" readonly>
    </div>
    <div class="col-span-full">
        <div class="p-3 bg-blue-50/50 border border-blue-100 rounded-lg text-xs text-blue-700 flex items-center gap-2">
            <i class="fas fa-info-circle text-blue-500"></i>
            <span>Sponsor relationships are immutable. To manage 1:3 matrix positions, use the <a href="{{ admin_route('mlm.tree.index') }}" class="underline font-semibold">Placement Tree</a> or Move Member tool.</span>
        </div>
    </div>
@endif

{{-- KYC & Banking Section --}}
@if($canViewSensitive)
    <div class="col-span-full pt-4">
        <h3 class="text-sm font-bold text-slate-900 pb-2 border-b border-slate-200/80 flex items-center gap-2">
            <i class="fas fa-id-card text-indigo-600 text-xs"></i> KYC & Banking Readiness
        </h3>
    </div>

    <div class="sm:col-span-1">
        <label class="block text-xs font-semibold text-slate-700 mb-1.5">KYC Status <span class="text-rose-500">*</span></label>
        <select name="kyc_status" class="w-full px-3 py-2 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 outline-none transition @error('kyc_status') border-rose-400 bg-rose-50/30 @enderror" required>
            @foreach(\App\Models\Member::KYC_STATUSES as $status)
                <option value="{{ $status }}" @selected(old('kyc_status', $member?->kyc_status ?? 'pending') === $status)>{{ ucwords(str_replace('_', ' ', $status)) }}</option>
            @endforeach
        </select>
        @error('kyc_status')<div class="text-rose-600 text-xs mt-1">{{ $message }}</div>@enderror
    </div>

    <div class="sm:col-span-1">
        <label class="block text-xs font-semibold text-slate-700 mb-1.5">PAN Card Number</label>
        <input type="text" name="pan_number" class="w-full px-3 py-2 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 outline-none transition font-mono uppercase @error('pan_number') border-rose-400 bg-rose-50/30 @enderror" value="{{ old('pan_number') }}" placeholder="10 characters (ABCDE1234F)">
        @error('pan_number')<div class="text-rose-600 text-xs mt-1">{{ $message }}</div>@enderror
    </div>

    <div class="sm:col-span-1">
        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Aadhaar Reference Number</label>
        <input type="text" name="aadhaar_reference" class="w-full px-3 py-2 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 outline-none transition font-mono @error('aadhaar_reference') border-rose-400 bg-rose-50/30 @enderror" value="{{ old('aadhaar_reference') }}" placeholder="12 digits">
        @error('aadhaar_reference')<div class="text-rose-600 text-xs mt-1">{{ $message }}</div>@enderror
    </div>

    <div class="sm:col-span-1">
        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Bank Account Holder Name</label>
        <input type="text" name="bank_account_holder_name" class="w-full px-3 py-2 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 outline-none transition @error('bank_account_holder_name') border-rose-400 bg-rose-50/30 @enderror" value="{{ old('bank_account_holder_name', $member?->bank_account_holder_name) }}" placeholder="Name as per bank records">
        @error('bank_account_holder_name')<div class="text-rose-600 text-xs mt-1">{{ $message }}</div>@enderror
    </div>

    <div class="sm:col-span-1">
        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Bank Account Number</label>
        <input type="text" name="bank_account_number" class="w-full px-3 py-2 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 outline-none transition font-mono @error('bank_account_number') border-rose-400 bg-rose-50/30 @enderror" value="{{ old('bank_account_number') }}" placeholder="Leave blank to keep existing">
        @error('bank_account_number')<div class="text-rose-600 text-xs mt-1">{{ $message }}</div>@enderror
    </div>

    <div class="sm:col-span-1">
        <label class="block text-xs font-semibold text-slate-700 mb-1.5">IFSC Code</label>
        <input type="text" name="ifsc_code" class="w-full px-3 py-2 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 outline-none transition font-mono uppercase @error('ifsc_code') border-rose-400 bg-rose-50/30 @enderror" value="{{ old('ifsc_code', $member?->ifsc_code) }}" placeholder="e.g. HDFC0001234">
        @error('ifsc_code')<div class="text-rose-600 text-xs mt-1">{{ $message }}</div>@enderror
    </div>

    <div class="sm:col-span-1">
        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Bank Name</label>
        <input type="text" name="bank_name" class="w-full px-3 py-2 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 outline-none transition" value="{{ old('bank_name', $member?->bank_name) }}" placeholder="e.g. HDFC Bank">
    </div>

    <div class="sm:col-span-1">
        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Bank Branch</label>
        <input type="text" name="bank_branch" class="w-full px-3 py-2 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 outline-none transition" value="{{ old('bank_branch', $member?->bank_branch) }}" placeholder="e.g. MG Road, Bengaluru">
    </div>

    <div class="sm:col-span-1">
        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Cancelled Cheque / Passbook Image</label>
        <input type="file" name="cancelled_cheque" class="w-full px-3 py-1.5 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 outline-none transition @error('cancelled_cheque') border-rose-400 bg-rose-50/30 @enderror" accept="image/jpeg,image/png,application/pdf">
        @error('cancelled_cheque')<div class="text-rose-600 text-xs mt-1">{{ $message }}</div>@enderror
    </div>

    <div class="col-span-full">
        <label class="block text-xs font-semibold text-slate-700 mb-1.5">KYC Rejection Reason (If Rejecting)</label>
        <textarea name="kyc_rejection_reason" rows="2" class="w-full px-3 py-2 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 outline-none transition @error('kyc_rejection_reason') border-rose-400 bg-rose-50/30 @enderror" placeholder="Explain rejection reason to distributor">{{ old('kyc_rejection_reason', $member?->kyc_rejection_reason) }}</textarea>
        @error('kyc_rejection_reason')<div class="text-rose-600 text-xs mt-1">{{ $message }}</div>@enderror
    </div>
@else
    @if(!$editing)
        <input type="hidden" name="kyc_status" value="{{ old('kyc_status', 'pending') }}">
    @endif
    <div class="col-span-full">
        <div class="p-3 bg-amber-50 border border-amber-200 rounded-lg text-xs text-amber-700">
            <i class="fas fa-lock mr-1.5"></i> Sensitive KYC fields are restricted to authorized Admin staff.
        </div>
    </div>
@endif

@if($editing)
    <div class="col-span-full pt-2">
        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Mobile Change Audit Reason</label>
        <textarea name="mobile_change_reason" rows="2" class="w-full px-3 py-2 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 outline-none transition @error('mobile_change_reason') border-rose-400 bg-rose-50/30 @enderror" placeholder="Mandatory if altering distributor mobile number">{{ old('mobile_change_reason') }}</textarea>
        @error('mobile_change_reason')<div class="text-rose-600 text-xs mt-1">{{ $message }}</div>@enderror
    </div>
@endif

@push('scripts')
<script>
    const sponsorInput = document.getElementById('sponsorCustomerId');
    const sponsorPreview = document.getElementById('sponsorPreview');
    const sponsorOptions = Array.from(document.querySelectorAll('#sponsor-options option'));
    if (sponsorInput && sponsorPreview) {
        const updateSponsorPreview = () => {
            const option = sponsorOptions.find(item => item.value === sponsorInput.value);
            sponsorPreview.textContent = option ? `Sponsor: ${option.dataset.name}` : 'Leave blank only for a confirmed root member.';
        };
        sponsorInput.addEventListener('input', updateSponsorPreview);
        updateSponsorPreview();
    }
</script>
@endpush
