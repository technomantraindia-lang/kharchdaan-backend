<div class="bg-white rounded-xl border border-slate-200/80 p-4 shadow-xs mb-6">
    <form method="GET" action="{{ $actionUrl ?? request()->url() }}" class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-wrap items-center gap-3">
            <div class="flex items-center gap-2 text-xs font-bold text-slate-600">
                <i class="fas fa-calendar-alt text-blue-500"></i>
                <span>Date Range:</span>
            </div>
            <div>
                <select name="date_preset" id="datePresetSelect" class="text-xs bg-slate-50 border border-slate-300 text-slate-800 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-none" onchange="toggleCustomDates(this.value)">
                    <option value="today" {{ ($range['preset'] ?? '') === 'today' ? 'selected' : '' }}>Today</option>
                    <option value="yesterday" {{ ($range['preset'] ?? '') === 'yesterday' ? 'selected' : '' }}>Yesterday</option>
                    <option value="last_7_days" {{ ($range['preset'] ?? '') === 'last_7_days' ? 'selected' : '' }}>Last 7 Days</option>
                    <option value="last_30_days" {{ ($range['preset'] ?? '') === 'last_30_days' ? 'selected' : '' }}>Last 30 Days</option>
                    <option value="this_month" {{ ($range['preset'] ?? '') === 'this_month' ? 'selected' : '' }}>This Month</option>
                    <option value="last_month" {{ ($range['preset'] ?? '') === 'last_month' ? 'selected' : '' }}>Last Month</option>
                    <option value="this_year" {{ ($range['preset'] ?? '') === 'this_year' ? 'selected' : '' }}>This Year</option>
                    <option value="custom" {{ ($range['preset'] ?? '') === 'custom' ? 'selected' : '' }}>Custom Date Range</option>
                </select>
            </div>
            <div class="custom-date-col {{ ($range['preset'] ?? '') === 'custom' ? '' : 'hidden' }}">
                <input type="date" name="date_from" value="{{ $range['date_from'] ?? '' }}" class="text-xs bg-slate-50 border border-slate-300 text-slate-800 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-none" placeholder="From Date">
            </div>
            <div class="custom-date-col {{ ($range['preset'] ?? '') === 'custom' ? '' : 'hidden' }}">
                <input type="date" name="date_to" value="{{ $range['date_to'] ?? '' }}" class="text-xs bg-slate-50 border border-slate-300 text-slate-800 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-none" placeholder="To Date">
            </div>
            <div class="flex items-center gap-2">
                <button type="submit" class="inline-flex items-center gap-1.5 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-xs font-semibold shadow-xs transition">
                    <i class="fas fa-filter text-xs"></i> Apply
                </button>
                <a href="{{ request()->url() }}" class="inline-flex items-center gap-1.5 px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold transition">
                    <i class="fas fa-rotate-left text-xs"></i> Reset
                </a>
            </div>
        </div>
        <div class="hidden lg:flex items-center gap-2">
            <span class="inline-flex items-center gap-2 px-3 py-1.5 bg-slate-50 text-slate-600 rounded-lg text-xs font-medium border border-slate-200">
                <i class="far fa-calendar-check text-blue-600"></i>
                <span>{{ Carbon\Carbon::parse($range['date_from'])->format('d M Y') }} &ndash; {{ Carbon\Carbon::parse($range['date_to'])->format('d M Y') }}</span>
            </span>
        </div>
    </form>
</div>

<script>
function toggleCustomDates(val) {
    const cols = document.querySelectorAll('.custom-date-col');
    cols.forEach(col => {
        if (val === 'custom') {
            col.classList.remove('hidden');
        } else {
            col.classList.add('hidden');
        }
    });
}
</script>
