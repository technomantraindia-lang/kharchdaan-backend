@if (session('success'))
    <div class="flex items-center justify-between gap-3 p-4 rounded-xl bg-emerald-50 border border-emerald-200/80 text-emerald-900 shadow-xs mb-6 transition duration-200" id="alert-success">
        <div class="flex items-center gap-3">
            <div class="w-8 h-8 rounded-lg bg-emerald-600 text-white flex items-center justify-center shrink-0 shadow-xs">
                <i class="fas fa-check"></i>
            </div>
            <div>
                <div class="font-bold text-sm text-emerald-950">Success!</div>
                <div class="text-xs text-emerald-800 mt-0.5">{{ session('success') }}</div>
            </div>
        </div>
        <button type="button" onclick="this.closest('#alert-success').remove()" class="text-emerald-500 hover:text-emerald-800 p-1.5 rounded-lg transition" title="Dismiss">
            <i class="fas fa-times text-sm"></i>
        </button>
    </div>
@endif

@if (session('error'))
    <div class="flex items-center justify-between gap-3 p-4 rounded-xl bg-rose-50 border border-rose-200/80 text-rose-900 shadow-xs mb-6 transition duration-200" id="alert-error">
        <div class="flex items-center gap-3">
            <div class="w-8 h-8 rounded-lg bg-rose-600 text-white flex items-center justify-center shrink-0 shadow-xs">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div>
                <div class="font-bold text-sm text-rose-950">Notice</div>
                <div class="text-xs text-rose-800 mt-0.5">{{ session('error') }}</div>
            </div>
        </div>
        <button type="button" onclick="this.closest('#alert-error').remove()" class="text-rose-500 hover:text-rose-800 p-1.5 rounded-lg transition" title="Dismiss">
            <i class="fas fa-times text-sm"></i>
        </button>
    </div>
@endif

@if (session('warning'))
    <div class="flex items-center justify-between gap-3 p-4 rounded-xl bg-amber-50 border border-amber-200/80 text-amber-900 shadow-xs mb-6 transition duration-200" id="alert-warning">
        <div class="flex items-center gap-3">
            <div class="w-8 h-8 rounded-lg bg-amber-500 text-white flex items-center justify-center shrink-0 shadow-xs">
                <i class="fas fa-bell"></i>
            </div>
            <div>
                <div class="font-bold text-sm text-amber-950">Warning</div>
                <div class="text-xs text-amber-800 mt-0.5">{{ session('warning') }}</div>
            </div>
        </div>
        <button type="button" onclick="this.closest('#alert-warning').remove()" class="text-amber-500 hover:text-amber-800 p-1.5 rounded-lg transition" title="Dismiss">
            <i class="fas fa-times text-sm"></i>
        </button>
    </div>
@endif

@if ($errors->any())
    <div class="p-4 rounded-xl bg-rose-50 border border-rose-200/80 text-rose-900 shadow-xs mb-6" id="alert-errors">
        <div class="flex items-start gap-3">
            <div class="w-8 h-8 rounded-lg bg-rose-600 text-white flex items-center justify-center shrink-0 mt-0.5 shadow-xs">
                <i class="fas fa-circle-exclamation"></i>
            </div>
            <div class="flex-1">
                <div class="font-bold text-sm text-rose-950">Please correct the following errors:</div>
                <ul class="list-disc list-inside text-xs text-rose-800 mt-1.5 space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            <button type="button" onclick="this.closest('#alert-errors').remove()" class="text-rose-500 hover:text-rose-800 p-1.5 rounded-lg transition" title="Dismiss">
                <i class="fas fa-times text-sm"></i>
            </button>
        </div>
    </div>
@endif
