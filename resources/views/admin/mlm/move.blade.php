@extends('admin.layouts.app')

@section('title', 'Move Member Placement Subtree: ' . ($member->customer_id ?? 'ID#'.$member->id))

@section('content')
<div class="space-y-6">
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight flex items-center gap-2.5">
                <i class="fas fa-arrows-split-up-and-left text-amber-500"></i> Move Placement Subtree
            </h1>
            <p class="text-sm text-slate-500 mt-1">
                The selected distributor and their entire 1:3 downline placement subtree will move atomically.
            </p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ admin_route('mlm.tree.index') }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold text-slate-700 bg-white border border-slate-200/80 rounded-lg hover:bg-slate-50 shadow-xs transition">
                <i class="fas fa-sitemap text-indigo-500"></i> Back to Placement Tree
            </a>
            <a href="{{ admin_route('mlm.members.show', $member) }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold text-slate-700 bg-white border border-slate-200/80 rounded-lg hover:bg-slate-50 shadow-xs transition">
                <i class="fas fa-user text-blue-500"></i> Member Details
            </a>
        </div>
    </div>

    @if(!$current_parent)
    <!-- Root Node Advisory -->
    <div class="p-4 bg-blue-50/90 border border-blue-200 rounded-xl flex items-start gap-3">
        <i class="fas fa-crown text-amber-500 mt-0.5 text-base"></i>
        <div class="text-xs text-blue-900 leading-relaxed">
            <span class="font-bold text-blue-950">Top Root Distributor Selected:</span> 
            <strong>{{ $member->user?->name }} ({{ $member->customer_id }})</strong> is the root node of the matrix with {{ $subtree_count }} network downlines. 
            Because all active distributors are in their downline subtree, downline members cannot be chosen as a parent without creating a cyclical reference. 
            To relocate a specific team or distributor branch, please click on that distributor in the <a href="{{ admin_route('mlm.tree.index') }}" class="underline font-bold text-blue-700 hover:text-blue-900">1:3 Placement Tree</a> and click <strong>Move Subtree</strong>.
        </div>
    </div>
    @endif

    <!-- Controlled Movement Notice -->
    <div class="p-4 bg-amber-50/80 border border-amber-200 rounded-xl flex items-start gap-3">
        <i class="fas fa-triangle-exclamation text-amber-600 mt-0.5 text-sm"></i>
        <div class="text-xs text-amber-800">
            <span class="font-bold">Controlled Audited Movement:</span> Sponsor genealogy relationships remain unchanged. The movement applies strictly to the 1:3 placement matrix and is committed only after reviewing the structural impact preview.
        </div>
    </div>

    <!-- Move Form & Node Details Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <!-- Selected Node Profile -->
        <div class="lg:col-span-5 bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden h-fit">
            <div class="px-5 py-4 border-b border-slate-100 bg-slate-50/50">
                <h2 class="text-sm font-bold text-slate-900">Current Node Placement</h2>
            </div>
            <div class="p-5 space-y-3 text-xs">
                <div class="flex justify-between py-2 border-b border-slate-100">
                    <span class="text-slate-500 font-medium">Selected Distributor</span>
                    <div class="text-right">
                        <span class="font-bold text-slate-900">{{ $member->user?->name }}</span>
                        <div class="font-mono text-[11px] text-blue-600 font-semibold">{{ $member->customer_id }}</div>
                    </div>
                </div>
                <div class="flex justify-between py-2 border-b border-slate-100">
                    <span class="text-slate-500 font-medium">Current Placement Parent</span>
                    <div class="text-right">
                        <span class="font-semibold text-slate-800">{{ $current_parent?->user?->name ?? 'None (Root Node)' }}</span>
                        @if($current_parent)
                            <div class="font-mono text-[11px] text-slate-500">{{ $current_parent->customer_id }}</div>
                        @endif
                    </div>
                </div>
                <div class="flex justify-between py-2 border-b border-slate-100">
                    <span class="text-slate-500 font-medium">Matrix Slot Position</span>
                    <span class="inline-flex px-2 py-0.5 rounded text-[11px] font-semibold bg-slate-100 text-slate-700 border border-slate-200 uppercase">
                        {{ $current_position ?: 'Root' }}
                    </span>
                </div>
                <div class="flex justify-between py-2 border-b border-slate-100">
                    <span class="text-slate-500 font-medium">Current Matrix Depth</span>
                    <span class="font-semibold text-slate-900">Level {{ $current_depth }}</span>
                </div>
                <div class="flex justify-between py-2">
                    <span class="text-slate-500 font-medium">Downline Members In Subtree</span>
                    <span class="font-bold text-blue-600 text-sm">{{ $subtree_count }} distributors</span>
                </div>
            </div>
        </div>

        <!-- Target Placement Form -->
        <div class="lg:col-span-7 bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100 bg-slate-50/50">
                <h2 class="text-sm font-bold text-slate-900">Target Parent & Slot Selection</h2>
            </div>

            <div class="p-5">
                <form id="movementForm" method="POST" action="{{ admin_route('mlm.tree.move', $member) }}" class="space-y-4 text-xs">
                    @csrf
                    <input type="hidden" name="new_parent_id" id="newParentId" value="{{ old('new_parent_id') }}">
                    <input type="hidden" name="confirmed" id="movementConfirmed" value="0">

                    <div>
                        <label for="newParentSearch" class="block font-semibold text-slate-700 mb-1.5 flex items-center justify-between">
                            <span>Search New Placement Parent</span>
                            <span class="text-[11px] font-normal text-slate-400">Live suggestions as you type</span>
                        </label>
                        <div class="relative">
                            <div class="flex gap-2">
                                <div class="relative flex-1">
                                    <i class="fas fa-search absolute left-3 top-2.5 text-slate-400 text-xs"></i>
                                    <input type="search" id="newParentSearch" class="w-full pl-8 pr-8 py-2 bg-slate-50 border border-slate-200 rounded-lg focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 outline-none transition" placeholder="Search Member ID, name, mobile or email..." autocomplete="off">
                                    <button type="button" id="clearParentSearch" class="hidden absolute right-2.5 top-2.5 text-slate-400 hover:text-slate-600 text-xs" title="Clear">
                                        <i class="fas fa-times-circle"></i>
                                    </button>
                                </div>
                                <button type="button" id="parentSearchButton" class="px-4 py-2 font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition flex items-center gap-1.5 shadow-xs">
                                    <i class="fas fa-search"></i> Search
                                </button>
                            </div>
                            <!-- Live Suggestions Floating Dropdown -->
                            <div id="parentSearchResults" class="hidden absolute left-0 right-0 top-full mt-1.5 bg-white rounded-xl shadow-2xl border border-slate-200/90 max-h-64 overflow-y-auto z-50 text-xs divide-y divide-slate-100"></div>
                        </div>
                        <div id="selectedParent" class="hidden mt-2 p-3 bg-emerald-50 border border-emerald-200 rounded-lg text-emerald-800 text-xs font-medium"></div>
                    </div>

                    <div>
                        <label for="newPosition" class="block font-semibold text-slate-700 mb-1.5">Target Placement Slot (Left / Middle / Right)</label>
                        <select name="new_position" id="newPosition" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 outline-none transition @error('new_position') border-rose-400 bg-rose-50/30 @enderror" disabled required>
                            <option value="">Search and select a parent first</option>
                        </select>
                        @error('new_position')<div class="text-rose-600 text-xs mt-1">{{ $message }}</div>@enderror
                    </div>

                    <div>
                        <label for="effectiveAt" class="block font-semibold text-slate-700 mb-1.5">Effective Date & Time</label>
                        <input type="datetime-local" name="effective_at" id="effectiveAt" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 outline-none transition @error('effective_at') border-rose-400 bg-rose-50/30 @enderror" value="{{ old('effective_at', now()->format('Y-m-d\TH:i')) }}" required>
                        @error('effective_at')<div class="text-rose-600 text-xs mt-1">{{ $message }}</div>@enderror
                    </div>

                    <div>
                        <label for="movementReason" class="block font-semibold text-slate-700 mb-1.5">Mandatory movement reason</label>
                        <textarea name="reason" id="movementReason" rows="3" maxlength="2000" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 outline-none transition @error('reason') border-rose-400 bg-rose-50/30 @enderror" placeholder="State operational reason for relocation..." required>{{ old('reason') }}</textarea>
                        @error('reason')<div class="text-rose-600 text-xs mt-1">{{ $message }}</div>@enderror
                    </div>

                    <button type="button" id="previewButton" class="w-full px-4 py-2.5 font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-lg shadow-xs transition flex items-center justify-center gap-1.5">
                        <i class="fas fa-eye"></i> Preview Movement
                    </button>
                    <div id="movementError" class="hidden p-3 bg-rose-50 border border-rose-200 rounded-lg text-rose-700 text-xs"></div>
                </form>
            </div>
        </div>
    </div>

    <!-- Movement Preview Modal / Card -->
    <div id="movementPreview" class="hidden bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100 bg-slate-50/50 flex items-center justify-between">
            <h2 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                <i class="fas fa-diagram-project text-blue-600"></i> Movement Impact Preview
            </h2>
            <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-amber-100 text-amber-800">Pending Confirmation</span>
        </div>

        <div class="p-5 space-y-5 text-xs">
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                <div class="p-3 bg-slate-50 rounded-lg border border-slate-200/80">
                    <span class="text-slate-500 text-[11px] font-semibold">Subtree Members</span>
                    <div id="previewCount" class="text-xl font-bold text-slate-900 mt-1"></div>
                </div>
                <div class="p-3 bg-slate-50 rounded-lg border border-slate-200/80">
                    <span class="text-slate-500 text-[11px] font-semibold">New Depth Level</span>
                    <div id="previewDepth" class="text-xl font-bold text-blue-600 mt-1"></div>
                </div>
                <div class="p-3 bg-slate-50 rounded-lg border border-slate-200/80">
                    <span class="text-slate-500 text-[11px] font-semibold">Target Slot Position</span>
                    <div id="previewPosition" class="text-xl font-bold text-emerald-600 mt-1"></div>
                </div>
                <div class="p-3 bg-slate-50 rounded-lg border border-slate-200/80">
                    <span class="text-slate-500 text-[11px] font-semibold">Effective Timestamp</span>
                    <div id="previewEffective" class="font-mono font-semibold text-slate-800 mt-1 text-[11px]"></div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-2">
                <div class="p-4 rounded-lg bg-slate-50 border border-slate-200/80">
                    <h3 class="font-bold text-slate-700 mb-2">Previous Placement Ancestry</h3>
                    <div id="previewOldPath" class="space-y-1"></div>
                </div>
                <div class="p-4 rounded-lg bg-blue-50/50 border border-blue-100">
                    <h3 class="font-bold text-blue-900 mb-2">New Target Placement Ancestry</h3>
                    <div id="previewNewPath" class="space-y-1"></div>
                </div>
            </div>

            <div class="p-4 bg-rose-50 border border-rose-200 rounded-lg text-rose-800 text-xs flex items-center justify-between gap-4">
                <div class="flex items-center gap-2">
                    <i class="fas fa-circle-exclamation text-rose-600"></i>
                    <span>Please verify tree paths carefully. Once confirmed, tree structures and calculation uplines are immediately updated.</span>
                </div>
                <button type="button" id="finalConfirmButton" class="px-5 py-2 text-xs font-semibold text-white bg-amber-600 hover:bg-amber-700 rounded-lg shadow-xs transition whitespace-nowrap">
                    Confirm and Save Movement
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(() => {
    const form = document.getElementById('movementForm');
    const parentSearch = document.getElementById('newParentSearch');
    const parentSearchButton = document.getElementById('parentSearchButton');
    const clearParentSearch = document.getElementById('clearParentSearch');
    const parentResults = document.getElementById('parentSearchResults');
    const selectedParent = document.getElementById('selectedParent');
    const parentId = document.getElementById('newParentId');
    const position = document.getElementById('newPosition');
    const previewButton = document.getElementById('previewButton');
    const preview = document.getElementById('movementPreview');
    const error = document.getElementById('movementError');
    const confirmField = document.getElementById('movementConfirmed');
    const csrf = form.querySelector('input[name="_token"]').value;
    const searchUrl = @json(admin_route('mlm.tree.move.parents.search', $member));
    const previewUrl = @json(admin_route('mlm.tree.move.preview', $member));
    let searchDebounceTimeout = null;

    const escapeHtml = (value) => String(value ?? '').replace(/[&<>'"]/g, (character) => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;'
    })[character]);

    const showError = (text) => { error.textContent = text; error.classList.remove('hidden'); };
    const clearError = () => { error.textContent = ''; error.classList.add('hidden'); };

    const setParent = (candidate) => {
        parentId.value = candidate.id;
        selectedParent.innerHTML = `
            <div class="flex items-center justify-between">
                <div>
                    <div class="flex items-center gap-1.5 font-bold text-emerald-900">
                        <i class="fas fa-check-circle text-emerald-600"></i>
                        <span>Selected Target Parent:</span>
                    </div>
                    <div class="mt-1 text-slate-800">
                        <span class="font-bold text-slate-900">${escapeHtml(candidate.name)}</span>
                        <span class="font-mono text-blue-600 font-semibold ml-1">(${escapeHtml(candidate.customer_id)})</span>
                        ${candidate.phone ? `<span class="text-slate-500 ml-1">&bull; ${escapeHtml(candidate.phone)}</span>` : ''}
                    </div>
                </div>
                <span class="px-2 py-0.5 rounded text-[11px] font-bold bg-emerald-100 text-emerald-800">
                    ${candidate.available_positions.length} Open Slot(s)
                </span>
            </div>`;
        selectedParent.classList.remove('hidden');

        position.replaceChildren(new Option('Select an available slot', ''));
        candidate.available_positions.forEach((slot) => {
            const opt = new Option(slot.charAt(0).toUpperCase() + slot.slice(1) + ' Slot', slot);
            position.append(opt);
        });
        position.disabled = candidate.available_positions.length === 0;
        if (candidate.available_positions.length > 0) {
            position.value = candidate.available_positions[0];
            position.focus();
        }
        parentResults.replaceChildren();
        parentResults.classList.add('hidden');
        parentSearch.value = `${candidate.name} (${candidate.customer_id})`;
        clearParentSearch.classList.remove('hidden');
    };

    const searchParents = async () => {
        const term = parentSearch.value.trim();
        clearError();

        if (!term) {
            parentResults.replaceChildren();
            parentResults.classList.add('hidden');
            clearParentSearch.classList.add('hidden');
            return;
        }

        clearParentSearch.classList.remove('hidden');

        if (term.length < 1) {
            parentResults.replaceChildren();
            parentResults.classList.add('hidden');
            return;
        }

        parentResults.innerHTML = `
            <div class="p-3 text-slate-500 text-center flex items-center justify-center gap-2">
                <i class="fas fa-spinner fa-spin text-blue-600"></i> Searching placement parents...
            </div>`;
        parentResults.classList.remove('hidden');

        try {
            const url = new URL(searchUrl, window.location.origin);
            url.searchParams.set('q', term);
            const response = await fetch(url, { headers: { Accept: 'application/json' } });
            const payload = await response.json();
            if (!response.ok) throw new Error(payload.message || 'Parent search failed.');
            
            parentResults.replaceChildren();

            if (!payload.data || !payload.data.length) {
                const emptyMsg = payload.message || 'No active parent with an available slot was found.';
                parentResults.innerHTML = `
                    <div class="p-4 text-center">
                        <i class="fas fa-info-circle text-slate-400 text-base mb-1 block"></i>
                        <span class="text-slate-600 font-medium">${escapeHtml(emptyMsg)}</span>
                    </div>`;
                parentResults.classList.remove('hidden');
                return;
            }

            payload.data.forEach((candidate) => {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'w-full text-left p-3 hover:bg-blue-50/80 transition flex items-center justify-between gap-3 text-xs';
                const slotsHtml = candidate.available_positions.map(s => 
                    `<span class="px-1.5 py-0.5 rounded font-mono font-bold text-[10px] bg-emerald-100 text-emerald-800 uppercase">${escapeHtml(s)}</span>`
                ).join(' ');

                button.innerHTML = `
                    <div class="min-w-0 flex-1">
                        <div class="font-bold text-slate-900 truncate">${escapeHtml(candidate.name)}</div>
                        <div class="text-[11px] text-slate-500 mt-0.5">
                            <span class="font-mono text-blue-600 font-semibold">${escapeHtml(candidate.customer_id)}</span>
                            ${candidate.phone ? `&bull; <span>${escapeHtml(candidate.phone)}</span>` : ''}
                        </div>
                    </div>
                    <div class="flex-shrink-0 text-right">
                        <div class="text-[10px] text-slate-400 font-semibold mb-0.5">Available Slots:</div>
                        <div class="flex items-center gap-1">${slotsHtml}</div>
                    </div>`;
                button.addEventListener('click', () => setParent(candidate));
                parentResults.append(button);
            });
            parentResults.classList.remove('hidden');
        } catch (requestError) {
            parentResults.innerHTML = `
                <div class="p-3 text-rose-600 text-center">
                    ${escapeHtml(requestError.message || 'Parent search failed.')}
                </div>`;
            parentResults.classList.remove('hidden');
        }
    };

    // Live Debounced Search Input
    parentSearch.addEventListener('input', () => {
        if (searchDebounceTimeout) clearTimeout(searchDebounceTimeout);
        searchDebounceTimeout = setTimeout(searchParents, 250);
    });

    clearParentSearch?.addEventListener('click', () => {
        parentSearch.value = '';
        parentId.value = '';
        selectedParent.classList.add('hidden');
        selectedParent.innerHTML = '';
        position.replaceChildren(new Option('Search and select a parent first', ''));
        position.disabled = true;
        parentResults.replaceChildren();
        parentResults.classList.add('hidden');
        clearParentSearch.classList.add('hidden');
        clearError();
        parentSearch.focus();
    });

    parentSearchButton.addEventListener('click', () => {
        if (searchDebounceTimeout) clearTimeout(searchDebounceTimeout);
        searchParents();
    });

    parentSearch.addEventListener('keydown', (event) => {
        if (event.key === 'Enter') {
            event.preventDefault();
            if (searchDebounceTimeout) clearTimeout(searchDebounceTimeout);
            searchParents();
        } else if (event.key === 'Escape') {
            parentResults.classList.add('hidden');
        }
    });

    // Close suggestions on outside click
    document.addEventListener('click', (e) => {
        if (!parentResults.contains(e.target) && e.target !== parentSearch && e.target !== parentSearchButton) {
            parentResults.classList.add('hidden');
        }
    });

    const renderPath = (path) => path.map((item, index) => `<span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold ${index === path.length - 1 ? 'bg-blue-600 text-white shadow-xs' : 'bg-white text-slate-700 border border-slate-200'} mr-1 mb-1">${escapeHtml(item.customer_id)} &bull; ${escapeHtml(item.name)}</span>`).join('<span class="text-slate-400 mr-1">&rsaquo;</span>');

    const previewMovement = async () => {
        clearError();
        if (!parentId.value || !position.value || !document.getElementById('movementReason').value.trim()) {
            showError('Please select a parent, available position slot, and provide an audit reason before previewing.');
            return;
        }
        previewButton.disabled = true;
        try {
            const body = new URLSearchParams(new FormData(form));
            const response = await fetch(previewUrl, { method: 'POST', headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf, 'Content-Type': 'application/x-www-form-urlencoded' }, body });
            const payload = await response.json();
            if (!response.ok) throw new Error(Object.values(payload.errors || {}).flat()[0] || payload.message || 'Movement preview failed.');
            const data = payload.data;
            document.getElementById('previewCount').textContent = data.affected_subtree_count + ' members';
            document.getElementById('previewDepth').textContent = 'Level ' + data.new_depth;
            document.getElementById('previewPosition').textContent = data.new_position.charAt(0).toUpperCase() + data.new_position.slice(1) + ' Slot';
            document.getElementById('previewEffective').textContent = data.effective_at;
            document.getElementById('previewOldPath').innerHTML = renderPath(data.old_path);
            document.getElementById('previewNewPath').innerHTML = renderPath(data.new_path);
            preview.classList.remove('hidden');
            preview.scrollIntoView({ behavior: 'smooth', block: 'start' });
        } catch (requestError) { showError(requestError.message || 'Movement preview failed.'); }
        finally { previewButton.disabled = false; }
    };

    previewButton.addEventListener('click', previewMovement);
    document.getElementById('finalConfirmButton').addEventListener('click', () => {
        if (window.confirm('Are you sure you want to relocate this member and their complete subtree?')) {
            confirmField.value = '1';
            form.submit();
        }
    });
})();
</script>
@endpush
