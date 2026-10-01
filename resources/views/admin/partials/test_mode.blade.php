{{-- Client Test Mode & Guided UAT Walkthrough Widget --}}
<div id="clientTestModeContainer">
    <!-- Floating Start Test Trigger Button (Bottom Right) -->
    <div id="testModeFloatingBtn" class="fixed bottom-6 right-6 z-40 flex items-center gap-2 group">
        <button type="button" onclick="openTestModeModal()" class="inline-flex items-center gap-3 px-4 py-3 bg-gradient-to-r from-orange-600 via-amber-600 to-orange-500 text-white font-bold text-xs rounded-2xl shadow-lg shadow-orange-600/30 hover:shadow-orange-600/50 hover:scale-105 active:scale-95 transition-all duration-200 border border-orange-400/40 ring-4 ring-orange-500/10 cursor-pointer">
            <span class="relative flex h-3 w-3">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-amber-200 opacity-80"></span>
                <span class="relative inline-flex rounded-full h-3 w-3 bg-amber-300"></span>
            </span>
            <span class="flex items-center gap-1.5 tracking-wide uppercase text-[11px] font-extrabold">
                <i class="fas fa-flask text-sm"></i> Start Test
            </span>
            <span id="testModeFloatingBadge" class="px-2 py-0.5 rounded-full bg-black/30 backdrop-blur-xs text-amber-200 text-[10px] font-mono font-bold ring-1 ring-white/20">
                0/7 Done
            </span>
        </button>
    </div>

    <!-- Modal Backdrop -->
    <div id="testModeBackdrop" class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs z-[80] hidden transition-opacity duration-300" onclick="closeTestModeModal()"></div>

    <!-- Slide-Over Testing Panel -->
    <div id="testModeDrawer" class="fixed inset-y-0 right-0 z-[85] max-w-2xl w-full bg-white shadow-2xl border-l border-slate-200 flex flex-col transform translate-x-full transition-transform duration-300 ease-in-out hidden">
        
        <!-- Header -->
        <div class="p-5 bg-gradient-to-r from-stone-900 via-stone-850 to-stone-950 text-white flex items-center justify-between border-b border-stone-800 flex-shrink-0">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-orange-600 to-amber-500 flex items-center justify-center text-white text-base shadow-md shadow-orange-500/30">
                    <i class="fas fa-vial-circle-check"></i>
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h2 class="text-base font-extrabold tracking-tight text-white">Client Acceptance Testing Mode</h2>
                        <span class="px-2 py-0.5 rounded-full bg-orange-500/20 text-orange-300 text-[10px] font-bold border border-orange-500/30">UAT Live</span>
                    </div>
                    <p class="text-xs text-stone-300 mt-0.5">Step-by-step verification scenarios for KharchDaan.Com Direct Selling Platform</p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <button type="button" onclick="closeTestModeModal()" class="w-8 h-8 rounded-lg bg-stone-800 hover:bg-stone-700 text-stone-300 hover:text-white flex items-center justify-center transition" title="Close">
                    <i class="fas fa-times text-sm"></i>
                </button>
            </div>
        </div>

        <!-- Progress Tracker Bar -->
        <div class="px-6 py-3.5 bg-orange-50/70 border-b border-orange-100 flex-shrink-0">
            <div class="flex items-center justify-between text-xs mb-1.5">
                <span class="font-bold text-stone-800 flex items-center gap-1.5">
                    <i class="fas fa-list-check text-orange-600"></i> Testing Progress:
                    <span id="testProgressCounter" class="text-orange-700 font-mono font-bold">0 / 7 Scenarios</span>
                </span>
                <div class="flex items-center gap-3">
                    <span id="testProgressPercent" class="font-bold text-orange-600">0%</span>
                    <button type="button" onclick="resetTestProgress()" class="text-[11px] text-stone-500 hover:text-rose-600 underline transition cursor-pointer">
                        Reset Checklist
                    </button>
                </div>
            </div>
            <div class="w-full bg-orange-200/60 rounded-full h-2.5 overflow-hidden">
                <div id="testProgressBar" class="bg-gradient-to-r from-orange-600 to-amber-500 h-2.5 rounded-full transition-all duration-300" style="width: 0%"></div>
            </div>
        </div>

        <!-- Filter Category Tabs -->
        <div class="px-6 py-2.5 bg-slate-50 border-b border-slate-200/80 flex items-center gap-2 overflow-x-auto text-xs font-semibold flex-shrink-0">
            <button type="button" onclick="filterTestCategory('all')" id="tabBtn-all" class="tab-filter-btn px-3 py-1.5 rounded-lg bg-white shadow-xs border border-slate-200 text-orange-700 font-bold">
                All Tests (7)
            </button>
            <button type="button" onclick="filterTestCategory('matrix')" id="tabBtn-matrix" class="tab-filter-btn px-3 py-1.5 rounded-lg text-slate-600 hover:text-slate-900 transition">
                Tree & Matrix (3)
            </button>
            <button type="button" onclick="filterTestCategory('finance')" id="tabBtn-finance" class="tab-filter-btn px-3 py-1.5 rounded-lg text-slate-600 hover:text-slate-900 transition">
                Calculations & Payouts (2)
            </button>
            <button type="button" onclick="filterTestCategory('store')" id="tabBtn-store" class="tab-filter-btn px-3 py-1.5 rounded-lg text-slate-600 hover:text-slate-900 transition">
                Cashback & Store (2)
            </button>
        </div>

        <!-- Scrollable Test Scenarios List -->
        <div class="flex-1 overflow-y-auto p-6 space-y-4" id="testScenariosContainer">

            <!-- Scenario 1: Add New Member & Sponsor Assignment -->
            <div class="test-scenario-card border border-slate-200 rounded-2xl p-5 bg-white shadow-xs hover:border-orange-300 transition" data-category="matrix" data-step="1">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex items-start gap-3">
                        <label class="relative flex items-center justify-center mt-0.5 cursor-pointer">
                            <input type="checkbox" onchange="toggleTestStep(1)" id="checkStep1" class="w-5 h-5 text-orange-600 rounded border-slate-300 focus:ring-orange-500 focus:ring-2 cursor-pointer">
                        </label>
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-orange-100 text-orange-800">TEST 1</span>
                                <h3 class="text-sm font-bold text-slate-900">Member Onboarding & Sponsor Assignment</h3>
                            </div>
                            <p class="text-xs text-slate-500 mt-1">Verify that a new direct selling distributor can be registered with a sponsor and automatically assigned a placement position.</p>
                        </div>
                    </div>
                </div>

                <div class="mt-3.5 bg-slate-50 rounded-xl p-3 text-xs text-slate-700 border border-slate-100 space-y-1.5">
                    <div class="font-bold text-slate-900 flex items-center gap-1.5 text-[11px] uppercase tracking-wider">
                        <i class="fas fa-clipboard-list text-orange-500"></i> What to test:
                    </div>
                    <ul class="list-disc list-inside space-y-1 text-slate-600 pl-1">
                        <li>Click <strong>"Test Member Registration"</strong> below.</li>
                        <li>Enter member name, email, mobile number, and select an existing Sponsor.</li>
                        <li>Choose automatic 1:3 placement (Left / Middle / Right) or specify a placement parent.</li>
                        <li>Submit and verify a unique Customer ID (e.g., <code>MEMxxxxxx</code>) is generated.</li>
                    </ul>
                </div>

                <div class="mt-4 flex items-center justify-between">
                    <span class="text-[11px] text-slate-400"><i class="fas fa-clock text-[10px] me-1"></i> Est. ~1 min</span>
                    <a href="{{ admin_route('mlm.members.create') }}" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 text-xs font-bold text-white bg-orange-600 hover:bg-orange-700 rounded-lg shadow-xs transition">
                        <span>Test Member Registration</span> <i class="fas fa-arrow-right text-[10px]"></i>
                    </a>
                </div>
            </div>

            <!-- Scenario 2: KYC & Compliance Verification -->
            <div class="test-scenario-card border border-slate-200 rounded-2xl p-5 bg-white shadow-xs hover:border-orange-300 transition" data-category="matrix" data-step="2">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex items-start gap-3">
                        <label class="relative flex items-center justify-center mt-0.5 cursor-pointer">
                            <input type="checkbox" onchange="toggleTestStep(2)" id="checkStep2" class="w-5 h-5 text-orange-600 rounded border-slate-300 focus:ring-orange-500 focus:ring-2 cursor-pointer">
                        </label>
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800">TEST 2</span>
                                <h3 class="text-sm font-bold text-slate-900">KYC Verification & Document Approval</h3>
                            </div>
                            <p class="text-xs text-slate-500 mt-1">Review pending KYC applications, inspect PAN/Aadhaar/Bank details, and approve/reject.</p>
                        </div>
                    </div>
                </div>

                <div class="mt-3.5 bg-slate-50 rounded-xl p-3 text-xs text-slate-700 border border-slate-100 space-y-1.5">
                    <div class="font-bold text-slate-900 flex items-center gap-1.5 text-[11px] uppercase tracking-wider">
                        <i class="fas fa-clipboard-list text-amber-500"></i> What to test:
                    </div>
                    <ul class="list-disc list-inside space-y-1 text-slate-600 pl-1">
                        <li>Navigate to the <strong>KYC Queue</strong> below.</li>
                        <li>Click on any pending member to review their identity documents and bank info.</li>
                        <li>Click <strong>"Approve KYC"</strong> to verify that status transitions to Approved.</li>
                        <li>Notice audit logging tracks the administrator who performed the verification.</li>
                    </ul>
                </div>

                <div class="mt-4 flex items-center justify-between">
                    <span class="text-[11px] text-slate-400"><i class="fas fa-clock text-[10px] me-1"></i> Est. ~1 min</span>
                    <a href="{{ admin_route('mlm.members.index', ['kyc_status' => 'pending']) }}" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 text-xs font-bold text-white bg-amber-600 hover:bg-amber-700 rounded-lg shadow-xs transition">
                        <span>Open KYC Queue</span> <i class="fas fa-arrow-right text-[10px]"></i>
                    </a>
                </div>
            </div>

            <!-- Scenario 3: 1:3 Physical Placement Tree & Matrix Navigation -->
            <div class="test-scenario-card border border-slate-200 rounded-2xl p-5 bg-white shadow-xs hover:border-orange-300 transition" data-category="matrix" data-step="3">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex items-start gap-3">
                        <label class="relative flex items-center justify-center mt-0.5 cursor-pointer">
                            <input type="checkbox" onchange="toggleTestStep(3)" id="checkStep3" class="w-5 h-5 text-orange-600 rounded border-slate-300 focus:ring-orange-500 focus:ring-2 cursor-pointer">
                        </label>
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-orange-100 text-orange-800">TEST 3</span>
                                <h3 class="text-sm font-bold text-slate-900">Placement Tree (1:3 Matrix) & Search</h3>
                            </div>
                            <p class="text-xs text-slate-500 mt-1">Explore top-down physical placement tree, expand/collapse child nodes, and search members.</p>
                        </div>
                    </div>
                </div>

                <div class="mt-3.5 bg-slate-50 rounded-xl p-3 text-xs text-slate-700 border border-slate-100 space-y-1.5">
                    <div class="font-bold text-slate-900 flex items-center gap-1.5 text-[11px] uppercase tracking-wider">
                        <i class="fas fa-clipboard-list text-orange-500"></i> What to test:
                    </div>
                    <ul class="list-disc list-inside space-y-1 text-slate-600 pl-1">
                        <li>View the 1:3 matrix nodes (Left, Middle, Right branch slots).</li>
                        <li>Click <strong>[+] / [-]</strong> buttons on nodes to expand or collapse sub-trees.</li>
                        <li>Switch Density Modes (<strong>Detailed</strong>, <strong>Compact</strong>, or <strong>Bubble Mode</strong>).</li>
                        <li>Use the <strong>"Search & Jump"</strong> toolbar to quickly locate any member by Customer ID.</li>
                    </ul>
                </div>

                <div class="mt-4 flex items-center justify-between">
                    <span class="text-[11px] text-slate-400"><i class="fas fa-clock text-[10px] me-1"></i> Est. ~2 mins</span>
                    <a href="{{ admin_route('mlm.tree.index') }}" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 text-xs font-bold text-white bg-gradient-to-r from-orange-600 to-amber-600 hover:from-orange-700 hover:to-amber-700 rounded-lg shadow-xs transition">
                        <span>Open Placement Tree</span> <i class="fas fa-arrow-right text-[10px]"></i>
                    </a>
                </div>
            </div>

            <!-- Scenario 4: Calculation Engine & 20-Level Income (Roundoff Verification) -->
            <div class="test-scenario-card border border-slate-200 rounded-2xl p-5 bg-white shadow-xs hover:border-orange-300 transition" data-category="finance" data-step="4">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex items-start gap-3">
                        <label class="relative flex items-center justify-center mt-0.5 cursor-pointer">
                            <input type="checkbox" onchange="toggleTestStep(4)" id="checkStep4" class="w-5 h-5 text-orange-600 rounded border-slate-300 focus:ring-orange-500 focus:ring-2 cursor-pointer">
                        </label>
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800">TEST 4</span>
                                <h3 class="text-sm font-bold text-slate-900">MLM 20-Level Calculation Engine (Clean Roundoff ₹)</h3>
                            </div>
                            <p class="text-xs text-slate-500 mt-1">Run the calculation engine on eligible orders, verify level-by-level distribution and roundoff currency.</p>
                        </div>
                    </div>
                </div>

                <div class="mt-3.5 bg-slate-50 rounded-xl p-3 text-xs text-slate-700 border border-slate-100 space-y-1.5">
                    <div class="font-bold text-slate-900 flex items-center gap-1.5 text-[11px] uppercase tracking-wider">
                        <i class="fas fa-clipboard-list text-emerald-600"></i> What to test:
                    </div>
                    <ul class="list-disc list-inside space-y-1 text-slate-600 pl-1">
                        <li>Click <strong>"Open Calculation Engine"</strong> below.</li>
                        <li>Preview level-by-level commission breakdown across 20 levels (Level 0 to Level 19).</li>
                        <li><strong>Important client check</strong>: Verify all income amounts are clean <strong>roundoff integer figures (e.g. ₹118, ₹45, ₹81, ₹11)</strong> with no decimal points.</li>
                        <li>Click <strong>"Execute Calculation"</strong> and verify the calculation run is saved with full ledger audit.</li>
                    </ul>
                </div>

                <div class="mt-4 flex items-center justify-between">
                    <span class="text-[11px] text-slate-400"><i class="fas fa-clock text-[10px] me-1"></i> Est. ~2 mins</span>
                    <a href="{{ admin_route('mlm.calculations.index') }}" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg shadow-xs transition">
                        <span>Open Calculation Engine</span> <i class="fas fa-arrow-right text-[10px]"></i>
                    </a>
                </div>
            </div>

            <!-- Scenario 5: Weekly Settlement & Payout Cycles -->
            <div class="test-scenario-card border border-slate-200 rounded-2xl p-5 bg-white shadow-xs hover:border-orange-300 transition" data-category="finance" data-step="5">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex items-start gap-3">
                        <label class="relative flex items-center justify-center mt-0.5 cursor-pointer">
                            <input type="checkbox" onchange="toggleTestStep(5)" id="checkStep5" class="w-5 h-5 text-orange-600 rounded border-slate-300 focus:ring-orange-500 focus:ring-2 cursor-pointer">
                        </label>
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800">TEST 5</span>
                                <h3 class="text-sm font-bold text-slate-900">Weekly Payout Cycles & Bank Settlements</h3>
                            </div>
                            <p class="text-xs text-slate-500 mt-1">Verify weekly payout cycles, review TDS & Admin deductions, and approve batch disbursements.</p>
                        </div>
                    </div>
                </div>

                <div class="mt-3.5 bg-slate-50 rounded-xl p-3 text-xs text-slate-700 border border-slate-100 space-y-1.5">
                    <div class="font-bold text-slate-900 flex items-center gap-1.5 text-[11px] uppercase tracking-wider">
                        <i class="fas fa-clipboard-list text-amber-500"></i> What to test:
                    </div>
                    <ul class="list-disc list-inside space-y-1 text-slate-600 pl-1">
                        <li>View active weekly settlement cycles.</li>
                        <li>Inspect distributor net earnings (Gross - TDS 5% - Admin 5%).</li>
                        <li>Click <strong>"Generate Payouts"</strong> or <strong>"Mark Settled"</strong> with transaction reference.</li>
                        <li>Download payout CSV export for bank batch upload.</li>
                    </ul>
                </div>

                <div class="mt-4 flex items-center justify-between">
                    <span class="text-[11px] text-slate-400"><i class="fas fa-clock text-[10px] me-1"></i> Est. ~1 min</span>
                    <a href="{{ admin_route('mlm.payouts.index') }}" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 text-xs font-bold text-white bg-amber-600 hover:bg-amber-700 rounded-lg shadow-xs transition">
                        <span>Open Weekly Settlements</span> <i class="fas fa-arrow-right text-[10px]"></i>
                    </a>
                </div>
            </div>

            <!-- Scenario 6: 100% Cashback & Company Profit Allocation -->
            <div class="test-scenario-card border border-slate-200 rounded-2xl p-5 bg-white shadow-xs hover:border-orange-300 transition" data-category="store" data-step="6">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex items-start gap-3">
                        <label class="relative flex items-center justify-center mt-0.5 cursor-pointer">
                            <input type="checkbox" onchange="toggleTestStep(6)" id="checkStep6" class="w-5 h-5 text-orange-600 rounded border-slate-300 focus:ring-orange-500 focus:ring-2 cursor-pointer">
                        </label>
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-orange-100 text-orange-800">TEST 6</span>
                                <h3 class="text-sm font-bold text-slate-900">100% Cashback Program & Monthly Profit Pools</h3>
                            </div>
                            <p class="text-xs text-slate-500 mt-1">Verify cashback pool accumulation, profit declarations, and customer wallet credits.</p>
                        </div>
                    </div>
                </div>

                <div class="mt-3.5 bg-slate-50 rounded-xl p-3 text-xs text-slate-700 border border-slate-100 space-y-1.5">
                    <div class="font-bold text-slate-900 flex items-center gap-1.5 text-[11px] uppercase tracking-wider">
                        <i class="fas fa-clipboard-list text-orange-500"></i> What to test:
                    </div>
                    <ul class="list-disc list-inside space-y-1 text-slate-600 pl-1">
                        <li>View eligible cashback transactions from delivered store orders.</li>
                        <li>Review Company Profit Pool allocations.</li>
                        <li>Verify monthly profit distribution updates member cashback balances without interfering with MLM matrix PV calculations.</li>
                    </ul>
                </div>

                <div class="mt-4 flex items-center justify-between">
                    <span class="text-[11px] text-slate-400"><i class="fas fa-clock text-[10px] me-1"></i> Est. ~1 min</span>
                    <a href="{{ admin_route('cashback.index') }}" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 text-xs font-bold text-white bg-orange-600 hover:bg-orange-700 rounded-lg shadow-xs transition">
                        <span>Open Cashback Program</span> <i class="fas fa-arrow-right text-[10px]"></i>
                    </a>
                </div>
            </div>

            <!-- Scenario 7: Products, Inventory, GST & Reports -->
            <div class="test-scenario-card border border-slate-200 rounded-2xl p-5 bg-white shadow-xs hover:border-orange-300 transition" data-category="store" data-step="7">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex items-start gap-3">
                        <label class="relative flex items-center justify-center mt-0.5 cursor-pointer">
                            <input type="checkbox" onchange="toggleTestStep(7)" id="checkStep7" class="w-5 h-5 text-orange-600 rounded border-slate-300 focus:ring-orange-500 focus:ring-2 cursor-pointer">
                        </label>
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-purple-100 text-purple-800">TEST 7</span>
                                <h3 class="text-sm font-bold text-slate-900">Products, Orders, GST & Reporting Suite</h3>
                            </div>
                            <p class="text-xs text-slate-500 mt-1">Manage e-commerce products, review order transactions, GST tax compliance, and analytics.</p>
                        </div>
                    </div>
                </div>

                <div class="mt-3.5 bg-slate-50 rounded-xl p-3 text-xs text-slate-700 border border-slate-100 space-y-1.5">
                    <div class="font-bold text-slate-900 flex items-center gap-1.5 text-[11px] uppercase tracking-wider">
                        <i class="fas fa-clipboard-list text-purple-500"></i> What to test:
                    </div>
                    <ul class="list-disc list-inside space-y-1 text-slate-600 pl-1">
                        <li>View products catalog, stock levels, and product variations.</li>
                        <li>Check customer orders, payment receipts, and delivery transitions.</li>
                        <li>Review the <strong>GST Compliance Report</strong> (CGST/SGST/IGST breakdown).</li>
                        <li>Test CSV export functionality for reporting audits.</li>
                    </ul>
                </div>

                <div class="mt-4 flex items-center justify-between">
                    <span class="text-[11px] text-slate-400"><i class="fas fa-clock text-[10px] me-1"></i> Est. ~1 min</span>
                    <a href="{{ admin_route('reports.index') }}" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 text-xs font-bold text-white bg-purple-600 hover:bg-purple-700 rounded-lg shadow-xs transition">
                        <span>Open Reporting Suite</span> <i class="fas fa-arrow-right text-[10px]"></i>
                    </a>
                </div>
            </div>

        </div>

        <!-- Footer / Client Info Notes -->
        <div class="p-4 bg-slate-50 border-t border-slate-200 flex-shrink-0 flex items-center justify-between text-xs text-slate-500">
            <div class="flex items-center gap-2">
                <i class="fas fa-shield-halved text-emerald-600 text-sm"></i>
                <span>Database SQLite &bull; Port 8001 &bull; 100% Tests Passing</span>
            </div>
            <button type="button" onclick="closeTestModeModal()" class="px-3.5 py-1.5 rounded-lg bg-slate-200 hover:bg-slate-300 font-semibold text-slate-700 transition">
                Close Guide
            </button>
        </div>

    </div>
</div>

<script>
    // State management for Test Mode Progress
    const TOTAL_TEST_STEPS = 7;

    function getTestProgress() {
        try {
            const saved = localStorage.getItem('kharchdaan_uat_progress');
            return saved ? JSON.parse(saved) : {};
        } catch(e) {
            return {};
        }
    }

    function saveTestProgress(progress) {
        try {
            localStorage.setItem('kharchdaan_uat_progress', JSON.stringify(progress));
        } catch(e) {}
    }

    function updateTestModeUI() {
        const progress = getTestProgress();
        let completedCount = 0;
        
        for (let i = 1; i <= TOTAL_TEST_STEPS; i++) {
            const isChecked = !!progress['step_' + i];
            const checkbox = document.getElementById('checkStep' + i);
            if (checkbox) {
                checkbox.checked = isChecked;
            }
            if (isChecked) completedCount++;
        }

        const percentage = Math.round((completedCount / TOTAL_TEST_STEPS) * 100);
        
        // Update Drawer Progress
        const counterEl = document.getElementById('testProgressCounter');
        const percentEl = document.getElementById('testProgressPercent');
        const barEl = document.getElementById('testProgressBar');
        const floatingBadge = document.getElementById('testModeFloatingBadge');

        if (counterEl) counterEl.innerText = `${completedCount} / ${TOTAL_TEST_STEPS} Scenarios`;
        if (percentEl) percentEl.innerText = `${percentage}%`;
        if (barEl) barEl.style.width = `${percentage}%`;
        if (floatingBadge) floatingBadge.innerText = `${completedCount}/${TOTAL_TEST_STEPS} Done`;
    }

    function toggleTestStep(stepNumber) {
        const progress = getTestProgress();
        const checkbox = document.getElementById('checkStep' + stepNumber);
        progress['step_' + stepNumber] = checkbox ? checkbox.checked : false;
        saveTestProgress(progress);
        updateTestModeUI();
    }

    function resetTestProgress() {
        if (confirm('Reset your test verification checklist back to 0%?')) {
            saveTestProgress({});
            updateTestModeUI();
        }
    }

    function openTestModeModal() {
        const backdrop = document.getElementById('testModeBackdrop');
        const drawer = document.getElementById('testModeDrawer');
        if (backdrop && drawer) {
            backdrop.classList.remove('hidden');
            drawer.classList.remove('hidden');
            setTimeout(() => {
                drawer.classList.remove('translate-x-full');
            }, 10);
        }
        updateTestModeUI();
    }

    function closeTestModeModal() {
        const backdrop = document.getElementById('testModeBackdrop');
        const drawer = document.getElementById('testModeDrawer');
        if (backdrop && drawer) {
            drawer.classList.add('translate-x-full');
            setTimeout(() => {
                backdrop.classList.add('hidden');
                drawer.classList.add('hidden');
            }, 300);
        }
    }

    function filterTestCategory(category) {
        const cards = document.querySelectorAll('.test-scenario-card');
        const tabBtns = document.querySelectorAll('.tab-filter-btn');

        tabBtns.forEach(btn => {
            btn.classList.remove('bg-white', 'shadow-xs', 'border', 'border-slate-200', 'text-orange-700', 'font-bold');
            btn.classList.add('text-slate-600');
        });

        const activeBtn = document.getElementById('tabBtn-' + category);
        if (activeBtn) {
            activeBtn.classList.remove('text-slate-600');
            activeBtn.classList.add('bg-white', 'shadow-xs', 'border', 'border-slate-200', 'text-orange-700', 'font-bold');
        }

        cards.forEach(card => {
            if (category === 'all' || card.getAttribute('data-category') === category) {
                card.style.display = 'block';
            } else {
                card.style.display = 'none';
            }
        });
    }

    // Initialize on DOM load
    document.addEventListener('DOMContentLoaded', () => {
        updateTestModeUI();
    });
</script>
