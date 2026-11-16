@extends('admin.layouts.app')

@section('title', 'Referral Eligibility (Structure)')

@section('content')
<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Referral Income Eligibility</h1>
        <p class="text-sm text-slate-500 mt-1">
            Weekly eligibility list per client rules (pt 2 / 20 / 21): CashBack &amp; Referral depend on Sanstha's profit;
            sponsor needs ≥ ₹{{ $threshold }} qualifying purchase in the month. Rupee payout % is not listed on the 38-point sheet — enter % when client provides it.
        </p>
    </div>

    <div class="bg-white rounded-xl border border-slate-200/80 p-5 shadow-xs">
        <form method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 text-xs">
            <div>
                <label class="block font-semibold text-slate-600 mb-1">Month</label>
                <input type="month" name="month" value="{{ $filters['month'] }}" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg">
            </div>
            <div>
                <label class="block font-semibold text-slate-600 mb-1">Week start</label>
                <input type="date" name="week_start" value="{{ $filters['week_start'] }}" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg">
            </div>
            <div>
                <label class="block font-semibold text-slate-600 mb-1">Search</label>
                <input type="text" name="q" value="{{ $filters['q'] }}" placeholder="Name / ID / phone" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg">
            </div>
            <div class="flex items-end">
                <label class="inline-flex items-center gap-2 text-slate-700">
                    <input type="checkbox" name="eligible_only" value="1" @checked($filters['eligible_only'])>
                    Eligible only
                </label>
            </div>
            <div class="flex items-end gap-2">
                <button class="flex-1 px-4 py-2 font-semibold text-white bg-blue-600 rounded-lg">Filter</button>
                <a href="{{ admin_route('mlm.referral-eligibility.index') }}" class="px-4 py-2 font-semibold text-slate-600 bg-slate-100 rounded-lg">Reset</a>
            </div>
        </form>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="px-5 py-3 border-b border-slate-100 flex flex-wrap items-center justify-between gap-2">
            <label class="inline-flex items-center gap-2 text-xs font-semibold text-slate-700">
                <input type="checkbox" id="referralSelectAll" class="rounded border-slate-300">
                Select All (eligible rows)
            </label>
            <button type="button" id="referralMarkReviewed" class="px-3 py-1.5 text-xs font-semibold text-white bg-blue-600 rounded-lg">
                Mark Selected as Reviewed (pending rate sheet)
            </button>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="border-b bg-slate-50 text-[11px] font-bold text-slate-500 uppercase">
                        <th class="py-3 px-4">Sel</th>
                        <th class="py-3 px-4">Sponsor</th>
                        <th class="py-3 px-4">Month Total</th>
                        <th class="py-3 px-4">Status</th>
                        <th class="py-3 px-4">Week Volume</th>
                        <th class="py-3 px-4">New Referrals (week)</th>
                        <th class="py-3 px-4">Reason</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($rows as $row)
                        <tr class="{{ $row['highlight_red'] ? 'bg-rose-50' : '' }}">
                            <td class="py-3 px-4">
                                <input type="checkbox" class="referral-row-check" value="{{ $row['sponsor_id'] }}" @disabled(! $row['referral_month_eligible'])>
                            </td>
                            <td class="py-3 px-4">
                                <div class="font-semibold">{{ $row['name'] }}</div>
                                <div class="font-mono text-blue-600">{{ $row['customer_id'] }}</div>
                                <div class="text-slate-400">{{ $row['phone'] }}</div>
                            </td>
                            <td class="py-3 px-4 font-semibold">₹{{ number_format((float) $row['month_purchase_total'], 2) }}</td>
                            <td class="py-3 px-4">
                                <span class="inline-flex px-2 py-0.5 rounded font-semibold {{ $row['referral_month_eligible'] ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700' }}">
                                    {{ $row['status_label'] }}
                                </span>
                            </td>
                            <td class="py-3 px-4">
                                ₹{{ number_format((float) $row['week_volume'], 2) }}
                                <div class="text-slate-400">{{ $row['week_txn_count'] }} txns · {{ $row['week_start'] }} → {{ $row['week_end'] }}</div>
                            </td>
                            <td class="py-3 px-4 font-semibold">{{ $row['week_new_referrals'] }}</td>
                            <td class="py-3 px-4 text-slate-600">{{ $row['reason'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-10 text-center text-slate-400">No sponsor rows for this filter.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
<script>
document.getElementById('referralSelectAll')?.addEventListener('change', function () {
    document.querySelectorAll('.referral-row-check:not(:disabled)').forEach((el) => { el.checked = this.checked; });
});
document.getElementById('referralMarkReviewed')?.addEventListener('click', function () {
    const ids = [...document.querySelectorAll('.referral-row-check:checked')].map((el) => el.value);
    if (!ids.length) {
        alert('Select at least one eligible sponsor row.');
        return;
    }
    alert('Marked ' + ids.length + ' sponsor(s) as reviewed. Paid amounts will apply after client referral % rate sheet is provided. Selected IDs: ' + ids.join(', '));
});
</script>
@endsection
