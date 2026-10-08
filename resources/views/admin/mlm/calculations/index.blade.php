@extends('admin.layouts.app')

@section('title', 'Direct Selling Calculations')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight flex items-center gap-2.5">
                <i class="fas fa-calculator text-blue-600"></i> Direct Selling Calculation Engine
            </h1>
            <p class="text-sm text-slate-500 mt-1">Automated 20-level PV distribution and commission engine adhering to KharchDaan.Com formula guidelines.</p>
        </div>
        <div class="flex items-center gap-2.5">
            <a href="{{ admin_route('mlm.levels') }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold text-slate-700 bg-white border border-slate-200/80 rounded-lg hover:bg-slate-50 shadow-xs transition">
                <i class="fas fa-layer-group text-blue-500"></i> Levels & Income Rules
            </a>
            <a href="{{ admin_route('mlm.payouts.index') }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold text-slate-700 bg-white border border-slate-200/80 rounded-lg hover:bg-slate-50 shadow-xs transition">
                <i class="fas fa-wallet text-amber-500"></i> Weekly Settlements
            </a>
        </div>
    </div>

    <!-- Formula Reference Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="bg-gradient-to-br from-blue-50/70 to-indigo-50/50 rounded-xl border border-blue-200/70 p-5 shadow-xs">
            <div class="flex items-center justify-between mb-3">
                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-blue-600 text-white shadow-xs">
                    HIGH PV TIER (Levels 0 to 7)
                </span>
                <span class="text-xs font-semibold text-blue-700">8 Upline Levels</span>
            </div>
            <div class="space-y-2 text-xs">
                <div>
                    <span class="text-slate-500 font-medium">PV Formula:</span>
                    <div class="font-mono font-bold text-slate-900 bg-white/80 px-3 py-1.5 rounded-lg border border-blue-100 mt-1">
                        PV = (Eligible Amount &times; 13.5) / 3000
                    </div>
                </div>
                <div>
                    <span class="text-slate-500 font-medium">Income Rate:</span>
                    <div class="font-mono font-bold text-emerald-700 bg-white/80 px-3 py-1.5 rounded-lg border border-blue-100 mt-1">
                        Income = PV &times; 0.20 (20%)
                    </div>
                </div>
                <div class="pt-1 text-[11px] text-slate-600">
                    Example on ₹1,000 purchase: <strong class="text-slate-900">4.50 PV</strong> &bull; <strong class="text-emerald-600">₹0.90 Income</strong>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-slate-50 to-amber-50/40 rounded-xl border border-amber-200/70 p-5 shadow-xs">
            <div class="flex items-center justify-between mb-3">
                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-600 text-white shadow-xs">
                    LOW PV TIER (Levels 8 to 19)
                </span>
                <span class="text-xs font-semibold text-amber-700">12 Upline Levels</span>
            </div>
            <div class="space-y-2 text-xs">
                <div>
                    <span class="text-slate-500 font-medium">PV Formula:</span>
                    <div class="font-mono font-bold text-slate-900 bg-white/80 px-3 py-1.5 rounded-lg border border-amber-100 mt-1">
                        PV = (Eligible Amount &times; 0.75) / 3000
                    </div>
                </div>
                <div>
                    <span class="text-slate-500 font-medium">Income Rate:</span>
                    <div class="font-mono font-bold text-emerald-700 bg-white/80 px-3 py-1.5 rounded-lg border border-amber-100 mt-1">
                        Income = PV &times; 0.20 (20%)
                    </div>
                </div>
                <div class="pt-1 text-[11px] text-slate-600">
                    Example on ₹1,000 purchase: <strong class="text-slate-900">0.25 PV</strong> &bull; <strong class="text-emerald-600">₹0.05 Income</strong>
                </div>
            </div>
        </div>
    </div>

    <!-- Manual Test Simulation Form Card -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
            <h2 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                <i class="fas fa-play text-emerald-600 text-xs"></i> Manual test transaction & calculation preview
            </h2>
            <span class="text-xs text-slate-500">Simulate 20-level distribution</span>
        </div>

        <form method="POST" action="{{ admin_route('mlm.calculations.preview') }}" class="p-5 space-y-4">
            @csrf
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="sm:col-span-2">
                    <label for="purchasingMember" class="block text-xs font-semibold text-slate-600 mb-1.5">Purchasing Member (Level 0)</label>
                    <select name="purchasing_member_id" id="purchasingMember" class="w-full px-3 py-2 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 outline-none transition" required>
                        <option value="">-- Select Member --</option>
                        @foreach($members as $memberOption)
                            <option value="{{ $memberOption->id }}" @selected((string) old('purchasing_member_id', $formData['purchasing_member_id'] ?? '') === (string) $memberOption->id)>
                                {{ $memberOption->customer_id }} — {{ $memberOption->user?->name }} ({{ ucfirst($memberOption->status) }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="eligibleAmount" class="block text-xs font-semibold text-slate-600 mb-1.5">Eligible Amount (₹)</label>
                    <input type="text" inputmode="decimal" name="eligible_amount" id="eligibleAmount" class="w-full px-3 py-2 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 outline-none transition font-semibold" value="{{ old('eligible_amount', $formData['eligible_amount'] ?? '1000.00') }}" placeholder="1000.00" required>
                </div>

                <div>
                    <label for="transactionReference" class="block text-xs font-semibold text-slate-600 mb-1.5">Transaction Reference</label>
                    <input type="text" name="transaction_reference" id="transactionReference" class="w-full px-3 py-2 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 outline-none transition font-mono" value="{{ old('transaction_reference', $formData['transaction_reference'] ?? 'TXN-'.time()) }}" placeholder="e.g. TEST-LEVEL-20" required>
                </div>

                <div>
                    <label for="transactionDate" class="block text-xs font-semibold text-slate-600 mb-1.5">Transaction Date</label>
                    <input type="datetime-local" name="transaction_date" id="transactionDate" class="w-full px-3 py-2 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 outline-none transition" value="{{ old('transaction_date', isset($formData['transaction_date']) ? \Carbon\Carbon::parse($formData['transaction_date'])->format('Y-m-d\TH:i') : now()->format('Y-m-d\TH:i')) }}" required>
                </div>

                <div>
                    <label for="ruleVersion" class="block text-xs font-semibold text-slate-600 mb-1.5">Rule Version</label>
                    <select name="rule_version_id" id="ruleVersion" class="w-full px-3 py-2 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 outline-none transition" required>
                        @foreach($rules as $rule)
                            <option value="{{ $rule->id }}" @selected((string) old('rule_version_id', $formData['rule_version_id'] ?? $rule->id) === (string) $rule->id)>
                                {{ $rule->version }} — {{ $rule->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="sm:col-span-2 flex items-end">
                    <button type="submit" class="w-full px-4 py-2.5 text-xs font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-lg shadow-xs transition flex items-center justify-center gap-1.5">
                        <i class="fas fa-eye"></i> Preview Level-by-Level Breakdown
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Calculation Preview Results -->
    @if($preview)
        @if(!empty($preview['already_exists']))
            <div class="p-4 rounded-xl bg-amber-50 border border-amber-200 text-amber-800 text-xs flex items-start gap-3">
                <i class="fas fa-triangle-exclamation text-amber-500 text-base mt-0.5"></i>
                <div>
                    <div class="font-bold text-amber-900">Transaction reference already processed!</div>
                    <div class="mt-0.5">Calculation run <code class="font-mono font-semibold">#{{ $preview['existing_run_id'] }}</code> exists for reference <code class="font-mono font-semibold">{{ $preview['transaction_reference'] }}</code>. Submitting again is idempotent and protected.</div>
                </div>
            </div>
        @endif

        <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                <h2 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                    <i class="fas fa-circle-check text-emerald-600"></i> Calculation Result & 20-Level Tree Breakdown
                </h2>
                <span class="px-2.5 py-0.5 rounded-full bg-blue-50 text-blue-700 border border-blue-200 text-[11px] font-bold">Rule {{ $preview['rule_version'] }}</span>
            </div>

            <!-- Preview Summary Stats -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 p-5 bg-slate-50/70 border-b border-slate-100">
                <div>
                    <div class="text-[11px] text-slate-500 font-semibold uppercase">Purchasing Member</div>
                    <div class="text-sm font-bold text-slate-900 mt-1">{{ $preview['purchasing_member']['name'] }}</div>
                    <div class="text-[11px] font-mono text-blue-600">{{ $preview['purchasing_member']['customer_id'] }}</div>
                </div>
                <div>
                    <div class="text-[11px] text-slate-500 font-semibold uppercase">Transaction Amount</div>
                    <div class="text-lg font-bold text-slate-900 mt-1">₹{{ number_format((float) $preview['eligible_amount'], 2) }}</div>
                </div>
                <div>
                    <div class="text-[11px] text-slate-500 font-semibold uppercase">Total PV Distributed</div>
                    <div class="text-lg font-mono font-bold text-indigo-600 mt-1">{{ number_format((float) $preview['total_pv'], 2) }} PV</div>
                </div>
                <div>
                    <div class="text-[11px] text-slate-500 font-semibold uppercase">Total Direct Selling Income</div>
                    <div class="text-lg font-bold text-emerald-600 mt-1">₹{{ number_format(round((float) $preview['total_income'])) }}</div>
                </div>
            </div>

            <!-- Breakdown Table -->
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-slate-200/80 bg-slate-50/75 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                            <th class="py-3 px-4">Level</th>
                            <th class="py-3 px-4">Beneficiary Member</th>
                            <th class="py-3 px-4">Income Type</th>
                            <th class="py-3 px-4">PV Formula</th>
                            <th class="py-3 px-4 text-right">PV Earned</th>
                            <th class="py-3 px-4">Income Formula</th>
                            <th class="py-3 px-4 text-right">Income (₹)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-xs text-slate-700">
                        @foreach($preview['lines'] as $line)
                            @php
                                $isHigh = $line['level'] <= 7;
                                $pvFormulaStr = "({$preview['eligible_amount']} × " . ($isHigh ? '13.5' : '0.75') . ") / 3000";
                                $incomeFormulaStr = number_format((float) $line['pv'], 2) . " PV × 20%";
                            @endphp
                            <tr class="hover:bg-slate-50/70 transition">
                                <td class="py-3 px-4">
                                    <span class="inline-flex px-2 py-0.5 rounded text-[11px] font-bold uppercase {{ $line['level'] === 0 ? 'bg-purple-50 text-purple-700 border border-purple-200' : ($isHigh ? 'bg-blue-50 text-blue-700 border border-blue-200' : 'bg-slate-100 text-slate-700 border border-slate-200') }}">
                                        Level {{ $line['level'] }}
                                    </span>
                                </td>
                                <td class="py-3 px-4">
                                    <div class="font-bold text-slate-900">{{ $line['member_name'] }}</div>
                                    <div class="text-[11px] font-mono text-slate-500">{{ $line['customer_id'] }}</div>
                                </td>
                                <td class="py-3 px-4">
                                    <span class="inline-flex px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-slate-100 text-slate-600">
                                        {{ str_replace('_', ' ', $line['income_type']) }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 font-mono text-slate-500 text-[11px]">{{ $pvFormulaStr }}</td>
                                <td class="py-3 px-4 text-right font-mono font-bold text-indigo-600">{{ number_format((float) $line['pv'], 2) }} PV</td>
                                <td class="py-3 px-4 font-mono text-slate-500 text-[11px]">{{ $incomeFormulaStr }}</td>
                                <td class="py-3 px-4 text-right font-bold text-emerald-600 text-sm">₹{{ number_format(round((float) $line['calculated_amount'])) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="bg-slate-50/90 border-t border-slate-200 font-bold text-xs text-slate-900">
                            <td colspan="4" class="py-3 px-4 text-right">Grand Totals:</td>
                            <td class="py-3 px-4 text-right font-mono text-indigo-700">{{ number_format((float) $preview['total_pv'], 2) }} PV</td>
                            <td></td>
                            <td class="py-3 px-4 text-right text-emerald-700 text-base">₹{{ number_format(round((float) $preview['total_income'])) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- Confirm Calculation Form -->
            <div class="p-5 bg-slate-50/50 border-t border-slate-100 flex items-center justify-end">
                <form method="POST" action="{{ admin_route('mlm.calculations.store') }}" onsubmit="return confirm('Commit these Direct Selling ledger entries?');">
                    @csrf
                    <input type="hidden" name="purchasing_member_id" value="{{ $formData['purchasing_member_id'] }}">
                    <input type="hidden" name="eligible_amount" value="{{ $formData['eligible_amount'] }}">
                    <input type="hidden" name="transaction_reference" value="{{ $formData['transaction_reference'] }}">
                    <input type="hidden" name="transaction_date" value="{{ $formData['transaction_date'] }}">
                    <input type="hidden" name="rule_version_id" value="{{ $formData['rule_version_id'] }}">
                    <input type="hidden" name="confirmed" value="1">
                    <button type="submit" class="px-5 py-2.5 text-xs font-semibold text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg shadow-xs transition flex items-center gap-2">
                        <i class="fas fa-check"></i> Commit Calculation to Ledger
                    </button>
                </form>
            </div>
        </div>
    @endif

    <!-- Calculation Audit History Card -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
            <h2 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                <i class="fas fa-clock-rotate-left text-slate-400"></i> Calculation History & Audit Log
            </h2>
            <span class="text-xs text-slate-500">{{ $runs->total() }} Total Calculation Runs</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-slate-200/80 bg-slate-50/75 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                        <th class="py-3 px-4">Transaction Ref</th>
                        <th class="py-3 px-4">Purchasing Member</th>
                        <th class="py-3 px-4">Rule Version</th>
                        <th class="py-3 px-4 text-right">Eligible Amount</th>
                        <th class="py-3 px-4 text-right">Total PV</th>
                        <th class="py-3 px-4 text-right">Total Income</th>
                        <th class="py-3 px-4">Processed Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-xs text-slate-700">
                    @forelse($runs as $run)
                        <tr class="hover:bg-slate-50/70 transition">
                            <td class="py-3 px-4 font-mono font-bold text-slate-900">{{ $run->source_transaction_reference }}</td>
                            <td class="py-3 px-4">
                                <div class="font-semibold text-slate-800">{{ $run->purchasingMember?->user?->name ?? 'Member' }}</div>
                                <div class="text-[11px] font-mono text-slate-500">{{ $run->purchasingMember?->customer_id }}</div>
                            </td>
                            <td class="py-3 px-4">
                                <span class="inline-flex px-2 py-0.5 rounded text-[11px] font-medium bg-slate-100 text-slate-700 border border-slate-200">
                                    {{ $run->ruleVersion?->version ?? 'v1.0' }}
                                </span>
                            </td>
                            <td class="py-3 px-4 text-right font-semibold text-slate-900">₹{{ number_format((float) $run->eligible_amount, 2) }}</td>
                            <td class="py-3 px-4 text-right font-mono font-bold text-indigo-600">{{ number_format((float) $run->total_pv, 2) }} PV</td>
                            <td class="py-3 px-4 text-right font-bold text-emerald-600">₹{{ number_format(round((float) $run->total_income)) }}</td>
                            <td class="py-3 px-4 text-slate-500 text-[11px]">
                                {{ $run->processed_at?->format('M d, Y H:i') ?? $run->created_at?->format('M d, Y H:i') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-12 text-center text-slate-400">
                                <i class="fas fa-calculator text-3xl mb-2 text-slate-300 block"></i>
                                No calculation history recorded yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($runs->hasPages())
            <div class="p-4 border-t border-slate-100 bg-slate-50/50 flex items-center justify-end">
                {{ $runs->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
