@extends('admin.layouts.app')

@section('title', 'Reports & Analytics')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-xl font-bold text-slate-900 tracking-tight">Platform Reports & Analytics</h1>
            <p class="text-xs text-slate-500 mt-0.5">Financial metrics, direct selling performance, and platform analytics for BachatGanga.Org</p>
        </div>
        <div>
            <a href="{{ admin_route('reports.export', ['module' => 'sales', 'date_preset' => $range['preset'], 'date_from' => $range['date_from'], 'date_to' => $range['date_to']]) }}" class="inline-flex items-center gap-1.5 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-semibold shadow-xs transition">
                <i class="fas fa-file-export text-xs"></i> Export Sales CSV
            </a>
        </div>
    </div>

    @include('admin.reports.partials.nav')
    @include('admin.reports.partials.filter')

    <!-- Direct Selling Performance Summary -->
    <div class="bg-white rounded-xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
            <h2 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                <i class="fas fa-network-wired text-blue-600"></i> Direct Selling & Network Financial Summary
            </h2>
            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold bg-blue-50 text-blue-700 border border-blue-200">
                <span class="w-1.5 h-1.5 rounded-full bg-blue-500 animate-pulse"></span> Network Engine Active
            </span>
        </div>
        <div class="p-5 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="p-4 rounded-xl bg-slate-50/70 border border-slate-200/60">
                <div class="text-xs font-bold text-slate-500 uppercase tracking-wider">Total Network Members</div>
                <div class="text-2xl font-extrabold text-slate-900 mt-1">{{ number_format($stats['total_members'] ?? 0) }}</div>
                <div class="text-xs text-emerald-600 font-semibold mt-1 flex items-center gap-1">
                    <i class="fas fa-circle-check text-[10px]"></i> {{ number_format($stats['active_members'] ?? 0) }} Active ({{ $stats['new_members'] ?? 0 }} in period)
                </div>
            </div>
            <div class="p-4 rounded-xl bg-slate-50/70 border border-slate-200/60">
                <div class="text-xs font-bold text-slate-500 uppercase tracking-wider">Total Network PV</div>
                <div class="text-2xl font-extrabold font-mono text-blue-600 mt-1">{{ number_format($stats['total_pv'] ?? 0, 2) }} <span class="text-xs font-sans text-slate-400 font-normal">PV</span></div>
                <div class="text-xs text-slate-400 mt-1">Levels 0–19 cumulative volume</div>
            </div>
            <div class="p-4 rounded-xl bg-slate-50/70 border border-slate-200/60">
                <div class="text-xs font-bold text-slate-500 uppercase tracking-wider">Direct Selling Income</div>
                <div class="text-2xl font-extrabold text-emerald-600 mt-1">₹{{ number_format($stats['total_direct_selling_income'] ?? 0, 2) }}</div>
                <div class="text-xs text-slate-400 mt-1">₹{{ number_format($stats['paid_payouts'] ?? 0, 2) }} paid out to members</div>
            </div>
            <div class="p-4 rounded-xl bg-slate-50/70 border border-slate-200/60">
                <div class="text-xs font-bold text-slate-500 uppercase tracking-wider">100% Cashback Eligible</div>
                <div class="text-2xl font-extrabold text-amber-600 mt-1">₹{{ number_format($stats['cashback_eligible_amount'] ?? 0, 2) }}</div>
                <div class="text-xs text-slate-400 mt-1">{{ $stats['cashback_eligible_count'] ?? 0 }} purchases eligible</div>
            </div>
        </div>
    </div>

    <!-- Sales & Order KPIs (4-card Grid) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl border border-slate-200/80 p-5 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Total Paid Revenue</span>
                <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center text-xs">
                    <i class="fas fa-rupee-sign"></i>
                </div>
            </div>
            <div class="text-2xl font-extrabold text-slate-900 mt-2">₹{{ number_format($stats['total_revenue'] ?? 0, 2) }}</div>
            <div class="text-xs text-slate-400 mt-1">Range: ₹{{ number_format($stats['range_revenue'] ?? 0, 2) }}</div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200/80 p-5 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">This Month Revenue</span>
                <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center text-xs">
                    <i class="fas fa-calendar-alt"></i>
                </div>
            </div>
            <div class="text-2xl font-extrabold text-slate-900 mt-2">₹{{ number_format($stats['month_revenue'] ?? 0, 2) }}</div>
            <div class="text-xs text-slate-400 mt-1">{{ $stats['month_orders'] ?? 0 }} orders this month</div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200/80 p-5 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Today's Revenue</span>
                <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center text-xs">
                    <i class="fas fa-clock"></i>
                </div>
            </div>
            <div class="text-2xl font-extrabold text-slate-900 mt-2">₹{{ number_format($stats['today_revenue'] ?? 0, 2) }}</div>
            <div class="text-xs text-slate-400 mt-1">{{ $stats['today_orders'] ?? 0 }} orders today</div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200/80 p-5 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Average Order Value</span>
                <div class="w-8 h-8 rounded-lg bg-purple-50 text-purple-600 flex items-center justify-center text-xs">
                    <i class="fas fa-shopping-bag"></i>
                </div>
            </div>
            <div class="text-2xl font-extrabold text-slate-900 mt-2">₹{{ number_format($stats['avg_order_value'] ?? 0, 2) }}</div>
            <div class="text-xs text-slate-400 mt-1">{{ $stats['total_orders'] ?? 0 }} total lifetime orders</div>
        </div>
    </div>

    <!-- Quick Statistics Badges (6 Columns) -->
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
        <div class="bg-white rounded-xl border border-slate-200/80 p-3 text-center shadow-xs">
            <div class="text-lg font-bold text-blue-600">{{ number_format($stats['total_customers'] ?? 0) }}</div>
            <div class="text-[11px] text-slate-500 mt-0.5">Customers</div>
        </div>
        <div class="bg-white rounded-xl border border-slate-200/80 p-3 text-center shadow-xs">
            <div class="text-lg font-bold text-emerald-600">{{ number_format($stats['total_products'] ?? 0) }}</div>
            <div class="text-[11px] text-slate-500 mt-0.5">Products</div>
        </div>
        <div class="bg-white rounded-xl border border-slate-200/80 p-3 text-center shadow-xs">
            <div class="text-lg font-bold text-amber-600">{{ number_format($stats['pending_orders'] ?? 0) }}</div>
            <div class="text-[11px] text-slate-500 mt-0.5">Pending Orders</div>
        </div>
        <div class="bg-white rounded-xl border border-slate-200/80 p-3 text-center shadow-xs">
            <div class="text-lg font-bold text-indigo-600">{{ number_format($stats['delivered_orders'] ?? 0) }}</div>
            <div class="text-[11px] text-slate-500 mt-0.5">Delivered Orders</div>
        </div>
        <div class="bg-white rounded-xl border border-slate-200/80 p-3 text-center shadow-xs">
            <div class="text-lg font-bold text-rose-600">{{ number_format($stats['low_stock_products'] ?? 0) }}</div>
            <div class="text-[11px] text-slate-500 mt-0.5">Low Stock Items</div>
        </div>
        <div class="bg-white rounded-xl border border-slate-200/80 p-3 text-center shadow-xs">
            <div class="text-lg font-bold text-slate-700">{{ number_format($stats['pending_inquiries'] ?? 0) }}</div>
            <div class="text-[11px] text-slate-500 mt-0.5">Support Inquiries</div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 bg-white rounded-xl border border-slate-200/80 p-5 shadow-xs">
            <h2 class="text-sm font-bold text-slate-900 mb-4 flex items-center gap-2">
                <i class="fas fa-chart-line text-blue-600"></i> Monthly Revenue Trend (Last 6 Months)
            </h2>
            <div class="h-64">
                <canvas id="monthlyChart"></canvas>
            </div>
        </div>
        <div class="bg-white rounded-xl border border-slate-200/80 p-5 shadow-xs flex flex-col">
            <h2 class="text-sm font-bold text-slate-900 mb-4 flex items-center gap-2">
                <i class="fas fa-chart-pie text-emerald-600"></i> Orders by Status
            </h2>
            <div class="flex-1 flex items-center justify-center">
                <div class="w-full max-w-[220px]">
                    <canvas id="statusChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Daily Sales & Payments Row -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-xl border border-slate-200/80 p-5 shadow-xs">
            <h2 class="text-sm font-bold text-slate-900 mb-4 flex items-center gap-2">
                <i class="fas fa-chart-bar text-indigo-600"></i> Daily Sales (Last 7 Days)
            </h2>
            <div class="h-56">
                <canvas id="dailyChart"></canvas>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-slate-200/80 p-5 shadow-xs">
            <h2 class="text-sm font-bold text-slate-900 mb-4 flex items-center gap-2">
                <i class="fas fa-credit-card text-amber-500"></i> Payments Breakdown by Method
            </h2>
            <div class="space-y-2.5 max-h-56 overflow-y-auto pr-1">
                @forelse($paymentsByMethod as $payment)
                <div class="flex items-center justify-between p-3 rounded-lg bg-slate-50 border border-slate-200/60">
                    <div>
                        <div class="font-bold text-xs text-slate-900">{{ strtoupper(str_replace('_', ' ', $payment->method)) }}</div>
                        <div class="text-[11px] text-slate-400 mt-0.5">{{ $payment->count }} transaction(s)</div>
                    </div>
                    <span class="font-bold text-xs text-blue-600">₹{{ number_format($payment->total, 2) }}</span>
                </div>
                @empty
                <div class="text-center py-8 text-slate-400">
                    <i class="far fa-folder-open text-2xl mb-1 block"></i>
                    <p class="text-xs">No payment data recorded in this period.</p>
                </div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Top Products & Categories Row -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <div class="lg:col-span-7 bg-white rounded-xl border border-slate-200/80 shadow-xs overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100">
                <h2 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                    <i class="fas fa-trophy text-amber-500"></i> Top Selling Products
                </h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-slate-700">
                    <thead class="bg-slate-50/80 text-slate-600 font-bold uppercase tracking-wider border-b border-slate-200/70 text-[10px]">
                        <tr>
                            <th class="px-5 py-3 w-12">#</th>
                            <th class="px-5 py-3">Product</th>
                            <th class="px-5 py-3">Category</th>
                            <th class="px-5 py-3 text-center">Units Sold</th>
                            <th class="px-5 py-3 text-right">Revenue</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-medium">
                        @forelse($topProducts as $i => $item)
                        <tr class="hover:bg-slate-50/60 transition">
                            <td class="px-5 py-3">
                                <span class="w-5 h-5 rounded-full flex items-center justify-center text-[10px] font-bold {{ $i < 3 ? 'bg-amber-100 text-amber-800' : 'bg-slate-100 text-slate-600' }}">
                                    {{ $i + 1 }}
                                </span>
                            </td>
                            <td class="px-5 py-3 font-bold text-slate-900">{{ $item->product?->name ?? 'Unknown Product' }}</td>
                            <td class="px-5 py-3">
                                <span class="inline-flex px-2 py-0.5 rounded bg-slate-100 text-slate-600 text-[10px] font-medium border border-slate-200">
                                    {{ $item->product?->category?->name ?? '-' }}
                                </span>
                            </td>
                            <td class="px-5 py-3 text-center font-bold text-slate-800">{{ $item->total_qty }}</td>
                            <td class="px-5 py-3 text-right font-bold text-emerald-600">₹{{ number_format($item->revenue, 2) }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="px-5 py-8 text-center text-slate-400">No product sales recorded yet.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="lg:col-span-5 bg-white rounded-xl border border-slate-200/80 p-5 shadow-xs">
            <h2 class="text-sm font-bold text-slate-900 mb-4 flex items-center gap-2">
                <i class="fas fa-layer-group text-blue-600"></i> Sales by Category
            </h2>
            <div class="space-y-4 max-h-72 overflow-y-auto pr-1">
                @forelse($categorySales as $cat)
                @php $maxRev = $categorySales->max('revenue') ?: 1; $pct = ($cat->revenue / $maxRev) * 100; @endphp
                <div>
                    <div class="flex items-center justify-between text-xs font-semibold text-slate-800 mb-1">
                        <span>{{ $cat->name }}</span>
                        <span class="text-blue-600">₹{{ number_format($cat->revenue, 2) }}</span>
                    </div>
                    <div class="w-full bg-slate-100 rounded-full h-1.5 overflow-hidden">
                        <div class="bg-blue-600 h-1.5 rounded-full" style="width: {{ $pct }}%;"></div>
                    </div>
                    <div class="text-[10px] text-slate-400 mt-0.5">{{ $cat->total_qty }} units sold</div>
                </div>
                @empty
                <div class="text-center py-8 text-slate-400">
                    <i class="far fa-folder-open text-2xl mb-1 block"></i>
                    <p class="text-xs">No category sales data available.</p>
                </div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Recent Orders Table -->
    <div class="bg-white rounded-xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
            <h2 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                <i class="fas fa-shopping-cart text-slate-700"></i> Recent Orders & Transactions
            </h2>
            <a href="{{ admin_route('orders.index') }}" class="text-xs font-semibold text-blue-600 hover:text-blue-700 transition">
                View All Orders &rarr;
            </a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-700">
                <thead class="bg-slate-50/80 text-slate-600 font-bold uppercase tracking-wider border-b border-slate-200/70 text-[10px]">
                    <tr>
                        <th class="px-5 py-3">Order #</th>
                        <th class="px-5 py-3">Customer</th>
                        <th class="px-5 py-3">Total Amount</th>
                        <th class="px-5 py-3">Order Status</th>
                        <th class="px-5 py-3">Payment Status</th>
                        <th class="px-5 py-3">Date</th>
                        <th class="px-5 py-3 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    @forelse($recentOrders as $order)
                    <tr class="hover:bg-slate-50/60 transition">
                        <td class="px-5 py-3 font-mono font-bold text-blue-600">{{ $order->order_num }}</td>
                        <td class="px-5 py-3 font-medium text-slate-900">{{ $order->user?->name ?? 'N/A' }}</td>
                        <td class="px-5 py-3 font-bold text-slate-900">₹{{ number_format($order->total, 2) }}</td>
                        <td class="px-5 py-3">
                            <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-bold uppercase {{ $order->status === 'delivered' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : ($order->status === 'cancelled' ? 'bg-rose-50 text-rose-700 border border-rose-200' : 'bg-blue-50 text-blue-700 border border-blue-200') }}">
                                {{ ucfirst($order->status) }}
                            </span>
                        </td>
                        <td class="px-5 py-3">
                            <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-bold uppercase {{ $order->pay_status === 'paid' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-amber-50 text-amber-700 border border-amber-200' }}">
                                {{ ucfirst($order->pay_status) }}
                            </span>
                        </td>
                        <td class="px-5 py-3 text-slate-400 text-[11px]">{{ $order->created_at->format('M d, Y h:i A') }}</td>
                        <td class="px-5 py-3 text-right">
                            <a href="{{ admin_route('orders.show', $order) }}" class="inline-flex items-center px-2.5 py-1 rounded bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition">
                                View
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-5 py-8 text-center text-slate-400">No recent orders found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@push('scripts')
<script>
const monthlyData = @json($monthlySales ?? []);
const statusData = @json($ordersByStatus ?? []);
const dailyData = @json($dailySales ?? []);

if (document.getElementById('monthlyChart') && monthlyData.length > 0) {
    new Chart(document.getElementById('monthlyChart'), {
        type: 'bar',
        data: {
            labels: monthlyData.map(d => d.label),
            datasets: [{
                label: 'Revenue (₹)',
                data: monthlyData.map(d => d.total),
                backgroundColor: 'rgba(37, 99, 235, 0.75)',
                borderColor: 'rgba(37, 99, 235, 1)',
                borderWidth: 1,
                borderRadius: 4,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function(context) { return '₹' + Number(context.raw).toLocaleString('en-IN', {minimumFractionDigits: 2}); }
                    }
                }
            },
            scales: { y: { beginAtZero: true } }
        }
    });
}

if (document.getElementById('statusChart')) {
    const statusColors = ['#f59e0b', '#3b82f6', '#06b6d4', '#10b981', '#ef4444'];
    new Chart(document.getElementById('statusChart'), {
        type: 'doughnut',
        data: {
            labels: Object.keys(statusData).map(s => s.charAt(0).toUpperCase() + s.slice(1)),
            datasets: [{
                data: Object.values(statusData),
                backgroundColor: statusColors,
                borderWidth: 2,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom' } }
        }
    });
}

if (document.getElementById('dailyChart') && dailyData.length > 0) {
    new Chart(document.getElementById('dailyChart'), {
        type: 'line',
        data: {
            labels: dailyData.map(d => d.label),
            datasets: [{
                label: 'Daily Sales (₹)',
                data: dailyData.map(d => d.total),
                borderColor: '#10b981',
                backgroundColor: 'rgba(16, 185, 129, 0.12)',
                fill: true,
                tension: 0.3,
                pointRadius: 4,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function(context) { return '₹' + Number(context.raw).toLocaleString('en-IN', {minimumFractionDigits: 2}); }
                    }
                }
            },
            scales: { y: { beginAtZero: true } }
        }
    });
}
</script>
@endpush
@endsection
