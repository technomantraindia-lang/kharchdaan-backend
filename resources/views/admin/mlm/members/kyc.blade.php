@extends('admin.layouts.app')

@section('title', 'Review KYC: ' . ($member->customer_id ?? 'ID#'.$member->id))

@section('content')
<div class="space-y-6">
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight flex items-center gap-2.5">
                <i class="fas fa-id-card text-indigo-600"></i> KYC & Compliance Review
            </h1>
            <p class="text-sm text-slate-500 mt-1">
                Distributor: <span class="font-semibold text-slate-800">{{ $member->user?->name }}</span> (<span class="font-mono font-semibold text-blue-600">{{ $member->customer_id }}</span>)
            </p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ admin_route('mlm.members.show', $member) }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold text-slate-700 bg-white border border-slate-200/80 rounded-lg hover:bg-slate-50 shadow-xs transition">
                <i class="fas fa-arrow-left text-slate-400"></i> Back to Profile
            </a>
        </div>
    </div>

    <!-- Review Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <!-- KYC Details Card -->
        <div class="lg:col-span-7 bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100 bg-slate-50/50 flex items-center justify-between">
                <h2 class="text-sm font-bold text-slate-900">Submitted KYC & Bank Information</h2>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold {{ match($member->kyc_status) { 'approved' => 'bg-emerald-50 text-emerald-700 border border-emerald-200', 'rejected' => 'bg-rose-50 text-rose-700 border border-rose-200', default => 'bg-amber-50 text-amber-700 border border-amber-200' } }}">
                    <span class="w-1.5 h-1.5 rounded-full {{ match($member->kyc_status) { 'approved' => 'bg-emerald-500', 'rejected' => 'bg-rose-500', default => 'bg-amber-500' } }} mr-1.5"></span>
                    {{ ucwords(str_replace('_', ' ', $member->kyc_status ?? 'pending')) }}
                </span>
            </div>

            <div class="p-5 space-y-3 text-xs">
                <div class="grid grid-cols-3 py-2 border-b border-slate-100">
                    <span class="text-slate-500 font-medium">PAN Card</span>
                    <span class="col-span-2 font-mono font-bold text-slate-900">{{ $member->masked_pan ?? 'Not supplied' }}</span>
                </div>
                <div class="grid grid-cols-3 py-2 border-b border-slate-100">
                    <span class="text-slate-500 font-medium">Aadhaar Ref</span>
                    <span class="col-span-2 font-mono text-slate-900">{{ $member->masked_aadhaar ?? 'Not supplied' }}</span>
                </div>
                <div class="grid grid-cols-3 py-2 border-b border-slate-100">
                    <span class="text-slate-500 font-medium">Account Holder</span>
                    <span class="col-span-2 font-semibold text-slate-900">{{ $member->bank_account_holder_name ?? '-' }}</span>
                </div>
                <div class="grid grid-cols-3 py-2 border-b border-slate-100">
                    <span class="text-slate-500 font-medium">Bank Account #</span>
                    <span class="col-span-2 font-mono font-bold text-slate-900">{{ $member->masked_bank_account ?? 'Not supplied' }}</span>
                </div>
                <div class="grid grid-cols-3 py-2 border-b border-slate-100">
                    <span class="text-slate-500 font-medium">IFSC Code</span>
                    <span class="col-span-2 font-mono text-slate-900">{{ $member->ifsc_code ?? '-' }}</span>
                </div>
                <div class="grid grid-cols-3 py-2 border-b border-slate-100">
                    <span class="text-slate-500 font-medium">Bank Name</span>
                    <span class="col-span-2 text-slate-900">{{ $member->bank_name ?? '-' }}</span>
                </div>
                <div class="grid grid-cols-3 py-2 border-b border-slate-100">
                    <span class="text-slate-500 font-medium">Bank Branch</span>
                    <span class="col-span-2 text-slate-900">{{ $member->bank_branch ?? '-' }}</span>
                </div>
                <div class="grid grid-cols-3 py-2">
                    <span class="text-slate-500 font-medium">Current Rejection Reason</span>
                    <span class="col-span-2 text-rose-600">{{ $member->kyc_rejection_reason ?? 'None' }}</span>
                </div>

                @if($member->cancelled_cheque_path)
                    <div class="pt-3 border-t border-slate-100">
                        <a href="{{ admin_route('mlm.members.kyc.cancelledCheque', $member) }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold text-blue-600 bg-blue-50 border border-blue-200 rounded-lg hover:bg-blue-100 transition">
                            <i class="fas fa-file-arrow-down"></i> Download Cancelled Cheque
                        </a>
                    </div>
                @else
                    <div class="pt-3 text-slate-400 text-xs italic">No cancelled cheque uploaded.</div>
                @endif
            </div>
        </div>

        <!-- Review Decision Action Card -->
        <div class="lg:col-span-5 bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100 bg-slate-50/50">
                <h2 class="text-sm font-bold text-slate-900">Admin Review Decision</h2>
            </div>

            <div class="p-5">
                @if(auth()->user()->hasPermission('mlm.kyc.review'))
                    <form method="POST" action="{{ admin_route('mlm.members.kyc.update', $member) }}" class="space-y-4 text-xs">
                        @csrf
                        @method('PATCH')

                        <div>
                            <label class="block font-semibold text-slate-700 mb-1.5">Update KYC Status <span class="text-rose-500">*</span></label>
                            <select name="kyc_status" class="w-full px-3 py-2 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 outline-none transition @error('kyc_status') border-rose-400 bg-rose-50/30 @enderror" required>
                                @foreach(\App\Models\Member::KYC_STATUSES as $status)
                                    <option value="{{ $status }}" @selected(old('kyc_status', $member->kyc_status) === $status)>{{ ucwords(str_replace('_', ' ', $status)) }}</option>
                                @endforeach
                            </select>
                            @error('kyc_status')<div class="text-rose-600 text-xs mt-1">{{ $message }}</div>@enderror
                        </div>

                        <div>
                            <label class="block font-semibold text-slate-700 mb-1.5">Rejection / Review Notes</label>
                            <textarea name="kyc_rejection_reason" rows="4" class="w-full px-3 py-2 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 outline-none transition @error('kyc_rejection_reason') border-rose-400 bg-rose-50/30 @enderror" placeholder="State reason if rejecting or requesting re-upload...">{{ old('kyc_rejection_reason') }}</textarea>
                            @error('kyc_rejection_reason')<div class="text-rose-600 text-xs mt-1">{{ $message }}</div>@enderror
                        </div>

                        <button type="submit" class="w-full px-4 py-2.5 text-xs font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-lg shadow-xs transition flex items-center justify-center gap-1.5">
                            <i class="fas fa-shield-check"></i> Save Verification Decision
                        </button>
                    </form>
                @else
                    <div class="p-4 bg-amber-50 border border-amber-200 rounded-lg text-xs text-amber-700">
                        <i class="fas fa-lock mr-1"></i> You do not have permission to review KYC submissions.
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- KYC Audit History Table -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
            <h2 class="text-sm font-bold text-slate-900">KYC Status Audit Trail</h2>
            <span class="text-xs text-slate-500">{{ count($member->kycHistories) }} Historic Updates</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-slate-200/80 bg-slate-50/75 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                        <th class="py-3 px-4">Previous Status</th>
                        <th class="py-3 px-4">New Status</th>
                        <th class="py-3 px-4">Reason / Notes</th>
                        <th class="py-3 px-4">Reviewed By</th>
                        <th class="py-3 px-4 text-right">Date & Time</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-xs text-slate-700">
                    @forelse($member->kycHistories as $history)
                        <tr class="hover:bg-slate-50/70 transition">
                            <td class="py-3 px-4 font-medium text-slate-500">{{ $history->old_status ? ucwords(str_replace('_', ' ', $history->old_status)) : '-' }}</td>
                            <td class="py-3 px-4">
                                <span class="inline-flex px-2 py-0.5 rounded text-[11px] font-semibold {{ match($history->new_status) { 'approved' => 'bg-emerald-50 text-emerald-700 border border-emerald-200', 'rejected' => 'bg-rose-50 text-rose-700 border border-rose-200', default => 'bg-amber-50 text-amber-700 border border-amber-200' } }}">
                                    {{ ucwords(str_replace('_', ' ', $history->new_status)) }}
                                </span>
                            </td>
                            <td class="py-3 px-4 text-slate-600">{{ $history->rejection_reason ?? '-' }}</td>
                            <td class="py-3 px-4 font-medium text-slate-900">{{ $history->changedBy?->name ?? 'System' }}</td>
                            <td class="py-3 px-4 text-right font-mono text-slate-500 text-[11px]">{{ $history->changed_at?->format('M d, Y h:i A') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-8 text-center text-slate-400">
                                No previous KYC status modifications recorded.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
