<div class="bg-white rounded-xl border border-slate-200/80 p-1.5 shadow-xs mb-6 overflow-x-auto">
    <nav class="flex items-center gap-1.5 min-w-max">
        <a href="{{ admin_route('reports.index') }}" class="inline-flex items-center gap-2 px-3.5 py-2 rounded-lg text-xs font-semibold transition {{ request()->routeIs('admin.reports.index') ? 'bg-blue-600 text-white shadow-xs' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
            <i class="fas fa-chart-pie text-xs"></i>
            <span>Overview & Revenue</span>
        </a>
        <a href="{{ admin_route('reports.network') }}" class="inline-flex items-center gap-2 px-3.5 py-2 rounded-lg text-xs font-semibold transition {{ request()->routeIs('admin.reports.network') ? 'bg-blue-600 text-white shadow-xs' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
            <i class="fas fa-sitemap text-xs"></i>
            <span>Network Reports</span>
        </a>
        <a href="{{ admin_route('reports.income') }}" class="inline-flex items-center gap-2 px-3.5 py-2 rounded-lg text-xs font-semibold transition {{ request()->routeIs('admin.reports.income') ? 'bg-blue-600 text-white shadow-xs' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
            <i class="fas fa-coins text-xs"></i>
            <span>Income Reports</span>
        </a>
        <a href="{{ admin_route('reports.payouts') }}" class="inline-flex items-center gap-2 px-3.5 py-2 rounded-lg text-xs font-semibold transition {{ request()->routeIs('admin.reports.payouts') ? 'bg-blue-600 text-white shadow-xs' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
            <i class="fas fa-wallet text-xs"></i>
            <span>Payout Reports</span>
        </a>
        <a href="{{ admin_route('reports.cashback') }}" class="inline-flex items-center gap-2 px-3.5 py-2 rounded-lg text-xs font-semibold transition {{ request()->routeIs('admin.reports.cashback') ? 'bg-blue-600 text-white shadow-xs' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
            <i class="fas fa-hand-holding-dollar text-xs"></i>
            <span>Cashback Reports</span>
        </a>
        <a href="{{ admin_route('reports.profit') }}" class="inline-flex items-center gap-2 px-3.5 py-2 rounded-lg text-xs font-semibold transition {{ request()->routeIs('admin.reports.profit') ? 'bg-blue-600 text-white shadow-xs' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
            <i class="fas fa-arrow-trend-up text-xs"></i>
            <span>Profit & Margins</span>
        </a>
        <a href="{{ admin_route('reports.gst') }}" class="inline-flex items-center gap-2 px-3.5 py-2 rounded-lg text-xs font-semibold transition {{ request()->routeIs('admin.reports.gst') ? 'bg-blue-600 text-white shadow-xs' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
            <i class="fas fa-receipt text-xs"></i>
            <span>GST & Tax</span>
        </a>
    </nav>
</div>
