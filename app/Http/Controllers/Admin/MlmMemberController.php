<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MoveMlmMemberRequest;
use App\Http\Requests\Admin\ReviewMlmKycRequest;
use App\Http\Requests\Admin\StoreMlmMemberRequest;
use App\Http\Requests\Admin\UpdateMlmMemberRequest;
use App\Models\CashbackEligibility;
use App\Models\Member;
use App\Models\MlmCalculationRun;
use App\Models\MlmIncomeLedger;
use App\Models\MlmPayoutLine;
use App\Models\User;
use App\Services\Mlm\MlmKycService;
use App\Services\Mlm\MlmMemberService;
use App\Services\Mlm\MlmMovementService;
use App\Services\Mlm\MlmTreeService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MlmMemberController extends Controller
{
    public function __construct(
        private readonly MlmMemberService $memberService,
        private readonly MlmMovementService $movementService,
        private readonly MlmKycService $kycService,
        private readonly MlmTreeService $treeService,
    ) {}

    public function index(Request $request): View
    {
        $members = Member::query()
            ->with(['user', 'sponsor.user', 'placementParent.user'])
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = trim((string) $request->input('search'));

                $isPgsql = \Illuminate\Support\Facades\DB::connection()->getDriverName() === 'pgsql';
                $like = $isPgsql ? 'ilike' : 'like';
                $lowerSearch = strtolower($search);

                $query->where(function ($memberQuery) use ($search, $like, $lowerSearch): void {
                    $memberQuery->whereHas('user', function ($userQuery) use ($search, $like, $lowerSearch): void {
                        $userQuery->where('mlm_member_id', $like, "%{$search}%")
                            ->orWhere('name', $like, "%{$search}%")
                            ->orWhere('phone', $like, "%{$search}%")
                            ->orWhere('email', $like, "%{$search}%")
                            ->orWhereRaw('LOWER(name) LIKE ?', ["%{$lowerSearch}%"])
                            ->orWhereRaw('LOWER(mlm_member_id) LIKE ?', ["%{$lowerSearch}%"]);
                    })->orWhereHas('sponsor.user', function ($sponsorQuery) use ($search, $like, $lowerSearch): void {
                        $sponsorQuery->where('mlm_member_id', $like, "%{$search}%")
                            ->orWhere('name', $like, "%{$search}%")
                            ->orWhereRaw('LOWER(name) LIKE ?', ["%{$lowerSearch}%"])
                            ->orWhereRaw('LOWER(mlm_member_id) LIKE ?', ["%{$lowerSearch}%"]);
                    });
                });
            })
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->input('status')))
            ->when($request->filled('kyc_status'), fn ($query) => $query->where('kyc_status', $request->input('kyc_status')))
            ->when($request->filled('joined_from'), fn ($query) => $query->whereDate('joined_at', '>=', $request->input('joined_from')))
            ->when($request->filled('joined_to'), fn ($query) => $query->whereDate('joined_at', '<=', $request->input('joined_to')))
            ->when($request->filled('sponsor_customer_id'), function ($query) use ($request): void {
                $sponsorId = trim((string) $request->input('sponsor_customer_id'));
                $query->whereHas('sponsor.user', fn ($sponsorQuery) => $sponsorQuery->where('mlm_member_id', $sponsorId));
            })
            ->latest('created_at')
            ->paginate(15)
            ->withQueryString();

        $sponsors = $this->sponsorOptions();
        $admins = User::query()
            ->whereHas('role', fn ($query) => $query->whereIn('name', ['Admin', 'Super Admin']))
            ->orderBy('name')
            ->get();

        return view('admin.mlm.members.index', compact('members', 'sponsors', 'admins'));
    }

    public function sponsorNetwork(Request $request): View
    {
        $requestedRoot = $request->integer('root');
        $search = $request->get('q');

        $root = null;
        if ($requestedRoot) {
            $root = Member::with(['user', 'sponsor.user'])->find($requestedRoot);
        }

        if (!$root) {
            $root = Member::query()
                ->with(['user', 'sponsor.user'])
                ->whereHas('user', fn ($q) => $q->where('mlm_member_id', 'C22020008'))
                ->first()
                ?? Member::query()
                    ->with(['user', 'sponsor.user'])
                    ->whereNull('sponsor_member_id')
                    ->orderBy('id')
                    ->first()
                ?? Member::with(['user', 'sponsor.user'])->orderBy('id')->first();
        }

        $networkData = $root ? $this->treeService->getSponsorNetwork($root) : null;
        $allMembers = Member::with('user')->get()->sortBy(fn ($m) => $m->customer_id === 'C22020008' ? -1 : (int) $m->id)->values();

        return view('admin.mlm.sponsor_network', compact('root', 'networkData', 'allMembers', 'search'));
    }

    public function genealogy(Request $request): View
    {
        $requestedRoot = $request->integer('root');
        $root = null;
        if ($requestedRoot) {
            $root = Member::with(['user', 'sponsor.user', 'placementParent.user'])->find($requestedRoot);
        }

        if (!$root) {
            $root = Member::query()
                ->with(['user', 'sponsor.user', 'placementParent.user'])
                ->whereHas('user', fn ($q) => $q->where('mlm_member_id', 'C22020008'))
                ->first()
                ?? Member::query()
                    ->with(['user', 'sponsor.user', 'placementParent.user'])
                    ->whereNull('placement_parent_id')
                    ->orderBy('id')
                    ->first()
                ?? Member::with(['user', 'sponsor.user', 'placementParent.user'])->orderBy('id')->first();
        }

        $genealogyData = $root ? $this->treeService->getGenealogy($root) : null;
        $roots = $this->treeService->roots();
        $allMembers = Member::with('user')->get()->sortBy(fn ($m) => $m->customer_id === 'C22020008' ? -1 : (int) $m->id)->values();

        return view('admin.mlm.genealogy', compact('root', 'genealogyData', 'roots', 'allMembers'));
    }

    public function levels(Request $request): View
    {
        $levelStats = $this->treeService->getLevelStatistics();

        $simAmount = $request->float('simulate_amount', 1000.0);
        $simMemberId = $request->integer('simulate_member_id');
        $simMember = $simMemberId ? Member::with('user')->find($simMemberId) : null;

        $simulation = $this->treeService->simulateLevelIncome($simAmount > 0 ? $simAmount : 1000.0, $simMember);
        $members = Member::with('user')->orderBy('id')->get();

        return view('admin.mlm.levels', compact('levelStats', 'simulation', 'simAmount', 'simMember', 'members'));
    }

    public function tree(Request $request): View
    {
        $roots = $this->treeService->roots();
        $requestedRoot = $request->integer('root');
        
        $root = $requestedRoot 
            ? Member::with(['user.addresses', 'sponsor.user', 'placementParent.user'])->find($requestedRoot) 
            : null;
        if (!$root) {
            $root = Member::query()
                ->with(['user.addresses', 'sponsor.user', 'placementParent.user'])
                ->whereHas('user', fn ($q) => $q->where('mlm_member_id', 'C22020008'))
                ->first()
                ?? $roots->first();
        }
        
        $initialRoot = null;
        if ($root) {
            $childrenData = $this->treeService->children($root);
            $initialRoot = [
                'member' => $childrenData['parent'],
                'slots' => $childrenData['slots'],
            ];
        }

        $leaderOptions = $this->treeService->leaderOptions();

        $rootPayloads = collect([$root])->filter()->map(function (Member $rootMember): array {
            $childrenData = $this->treeService->children($rootMember);
            return [
                'member' => $childrenData['parent'],
                'slots' => $childrenData['slots'],
            ];
        })->values();

        return view('admin.mlm.tree', compact('roots', 'initialRoot', 'rootPayloads', 'root', 'leaderOptions'));
    }

    public function treeChildren(Member $member): JsonResponse
    {
        return response()->json($this->treeService->children($member));
    }

    public function treeSearch(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['required', 'string', 'min:1', 'max:100'],
        ]);

        return response()->json([
            'data' => $this->treeService->search(trim($validated['q'])),
        ]);
    }

    public function assignAppreciation(Request $request, Member $member): JsonResponse
    {
        abort_unless($this->can('mlm.manage'), 403, 'Unauthorized action.');
        $term = trim((string) (
            $request->input('beneficiary')
            ?? $request->input('beneficiary_member_id')
            ?? $request->input('assigned_appreciation_member_id')
            ?? $request->input('member_id')
            ?? $request->input('customer_id')
        ));

        if (! $term && $member->appreciation_member_id) {
            $beneficiary = Member::with('user')->find($member->appreciation_member_id);
        } else {
            $beneficiary = Member::with('user')
                ->where('id', $term)
                ->orWhereHas('user', fn ($q) => $q->where('mlm_member_id', $term))
                ->first();
        }

        if (! $beneficiary) {
            return response()->json([
                'success' => false,
                'message' => "Could not find member with Code or ID '{$term}'.",
            ], 404);
        }

        $durationType = $request->input('duration_type', 'days');
        $expiresAt = null;

        if ($durationType === 'days' && $request->filled('duration_days') && (int) $request->input('duration_days') > 0) {
            $expiresAt = now()->addDays((int) $request->input('duration_days'))->endOfDay();
        } elseif ($durationType === 'date' && $request->filled('expires_at')) {
            try {
                $expiresAt = \Carbon\Carbon::parse($request->input('expires_at'));
            } catch (\Throwable) {
                $expiresAt = null;
            }
        } elseif ($durationType === 'indefinite') {
            $expiresAt = null;
        } elseif ($request->filled('expires_at')) {
            try {
                $expiresAt = \Carbon\Carbon::parse($request->input('expires_at'));
            } catch (\Throwable) {
                $expiresAt = null;
            }
        } elseif ($request->filled('duration_days') && (int) $request->input('duration_days') > 0) {
            $expiresAt = now()->addDays((int) $request->input('duration_days'))->endOfDay();
        }

        $slotName = $member->idle_slot_label ?? "Idle Slot #{$member->id}";

        $member->update([
            'appreciation_member_id' => $beneficiary->id,
            'appreciation_assigned_at' => $member->appreciation_assigned_at ?? now(),
            'appreciation_expires_at' => $expiresAt,
            'appreciation_note' => $request->input('note') ?? ($member->appreciation_note ?? 'Temporary Promotion / Appreciation Bonus'),
        ]);

        $expiryMsg = $expiresAt
            ? " Active until {$expiresAt->format('d M Y, h:i A')}."
            : " Set as flexible / indefinite until manually reverted.";

        return response()->json([
            'success' => true,
            'message' => "Successfully promoted {$beneficiary->user?->name} ({$beneficiary->customer_id}) to {$slotName}!{$expiryMsg}",
            'member' => $this->treeService->memberPayload($member->fresh(['user', 'appreciationMember.user'])),
        ]);
    }

    public function unassignAppreciation(Member $member): JsonResponse
    {
        abort_unless($this->can('mlm.manage'), 403, 'Unauthorized action.');

        $slotName = $member->idle_slot_label ?? "Idle Slot #{$member->id}";

        $member->update([
            'appreciation_member_id' => null,
            'appreciation_assigned_at' => null,
            'appreciation_expires_at' => null,
            'appreciation_note' => null,
        ]);

        return response()->json([
            'success' => true,
            'message' => "Temporary promotion reverted. {$slotName} is now restored to an unoccupied Idle Position.",
            'member' => $this->treeService->memberPayload($member->fresh(['user', 'appreciationMember.user'])),
        ]);
    }

    public function idleSlots(Request $request): JsonResponse
    {
        abort_unless($this->can('mlm.view'), 403, 'Unauthorized action.');

        return response()->json([
            'success' => true,
            'slots' => $this->treeService->allIdleSlots(),
        ]);
    }

    public function searchPromotable(Request $request): JsonResponse
    {
        abort_unless($this->can('mlm.view'), 403, 'Unauthorized action.');
        $term = trim((string) $request->input('q', ''));
        if (strlen($term) < 2) {
            return response()->json(['data' => []]);
        }

        return response()->json([
            'data' => $this->treeService->searchPromotableMembers($term),
        ]);
    }

    public function moveForm(Member $member): View
    {
        abort_unless($this->can('mlm.move'), 403, 'Unauthorized action.');

        return view('admin.mlm.move', $this->movementService->context($member));
    }

    public function moveParentSearch(Request $request, Member $member): JsonResponse
    {
        abort_unless($this->can('mlm.move'), 403, 'Unauthorized action.');
        $validated = $request->validate([
            'q' => ['required', 'string', 'min:1', 'max:100'],
        ]);

        $term = trim($validated['q']);
        $candidates = $this->movementService->searchParents($member, $term);
        $isRoot = $member->placement_parent_id === null;

        return response()->json([
            'data' => $candidates,
            'is_root' => $isRoot,
            'message' => empty($candidates) && $isRoot
                ? 'This member is the Top Root Node of the 1:3 matrix. All network members belong to their downline subtree and cannot be selected as a parent without creating a cycle.'
                : (empty($candidates) ? 'No active placement parent with an open slot was found.' : null),
        ]);
    }

    public function movePreview(MoveMlmMemberRequest $request, Member $member): JsonResponse
    {
        abort_unless($this->can('mlm.move'), 403, 'Unauthorized action.');

        try {
            return response()->json([
                'data' => $this->movementService->preview($member, $request->validated()),
            ]);
        } catch (ValidationException $exception) {
            return response()->json([
                'message' => 'The movement preview is invalid.',
                'errors' => $exception->errors(),
            ], 422);
        }
    }

    public function move(MoveMlmMemberRequest $request, Member $member): RedirectResponse|JsonResponse
    {
        abort_unless($this->can('mlm.move'), 403, 'Unauthorized action.');
        if (! $request->boolean('confirmed')) {
            if ($request->wantsJson() || $request->ajax() || $request->isJson() || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Confirm the movement preview before saving.',
                ], 422);
            }
            throw ValidationException::withMessages([
                'confirmed' => 'Confirm the movement preview before saving.',
            ]);
        }

        try {
            $movement = $this->movementService->move($member, $request->validated(), $request->user());
        } catch (ValidationException $e) {
            if ($request->wantsJson() || $request->ajax() || $request->isJson() || $request->expectsJson()) {
                $msg = $e->validator?->errors()->first() ?: (collect($e->errors())->flatten()->first() ?: $e->getMessage());
                return response()->json([
                    'success' => false,
                    'message' => $msg,
                    'errors' => $e->errors(),
                ], 422);
            }
            throw $e;
        }

        if ($request->wantsJson() || $request->ajax() || $request->isJson() || $request->expectsJson()) {
            $freshMember = $member->fresh(['user', 'sponsor.user', 'placementParent.user']);
            $newPath = $this->movementService->placementPath($freshMember) ?? [$freshMember->id];
            return response()->json([
                'success' => true,
                'message' => 'Placement subtree moved successfully.',
                'movement_id' => $movement->id,
                'old_parent_id' => $movement->old_parent_id,
                'new_parent_id' => $movement->new_parent_id,
                'member_id' => $freshMember->id,
                'new_position' => $movement->new_position,
                'path' => $newPath,
                'member' => $this->treeService->memberPayload($freshMember),
                'target_url' => admin_route('mlm.tree.index', ['root' => $member->id]),
            ]);
        }

        return redirect()->route('admin.mlm.members.show', $member)
            ->with('success', 'Placement subtree moved successfully.');
    }

    public function create(): View
    {
        return view('admin.mlm.members.create', $this->formData());
    }

    public function store(StoreMlmMemberRequest $request): RedirectResponse
    {
        $this->guardKycSubmission($request);

        $member = $this->memberService->create(
            $request->validated(),
            $request->user(),
            $request->file('profile_photo'),
            $request->file('cancelled_cheque')
        );

        return redirect()->route('admin.mlm.members.show', $member)
            ->with('success', 'Member created successfully.');
    }

    public function show(Member $member): View
    {
        $member->load([
            'user',
            'sponsor.user',
            'placementParent.user',
            'placementChildren.user',
            'createdBy',
            'kycVerifiedBy'
        ]);

        $canViewSensitive = $this->can('mlm.kyc.view');
        $canViewActivity = $this->can('mlm.activity.view');

        // Level & Placement
        $placementLevel = $this->treeService->level($member);

        // Network Statistics
        $directReferrals = Member::query()->with('user')->where('sponsor_member_id', $member->id)->get();
        $placementSlots = $this->treeService->children($member)['slots'];

        // Income & Financial Stats
        $totalPv = (float) MlmIncomeLedger::where('member_id', $member->id)->where('status', '!=', MlmIncomeLedger::STATUS_FAILED)->sum('pv');
        $totalIncome = (float) MlmIncomeLedger::where('member_id', $member->id)->where('status', '!=', MlmIncomeLedger::STATUS_FAILED)->sum('calculated_amount');
        $paidIncome = (float) MlmIncomeLedger::where('member_id', $member->id)->where('status', MlmIncomeLedger::STATUS_PAID)->sum('calculated_amount');
        $pendingIncome = (float) MlmIncomeLedger::where('member_id', $member->id)->whereIn('status', [MlmIncomeLedger::STATUS_CALCULATED, MlmIncomeLedger::STATUS_PENDING_APPROVAL, MlmIncomeLedger::STATUS_APPROVED, MlmIncomeLedger::STATUS_PROCESSING])->sum('calculated_amount');

        // Calculations & Ledgers
        $calculations = MlmCalculationRun::query()
            ->with(['ruleVersion', 'purchasingMember.user'])
            ->where('purchasing_member_id', $member->id)
            ->latest()
            ->limit(20)
            ->get();

        $incomeLedgers = MlmIncomeLedger::query()
            ->with(['purchasingMember.user', 'ruleVersion', 'payoutCycle'])
            ->where('member_id', $member->id)
            ->latest('transaction_date')
            ->paginate(15, ['*'], 'ledgers_page')
            ->withQueryString();

        // Payouts
        $payoutLines = MlmPayoutLine::query()
            ->with('cycle')
            ->where('member_id', $member->id)
            ->latest()
            ->get();

        // Cashback
        $cashbacks = CashbackEligibility::query()
            ->with('order')
            ->where('member_id', $member->id)
            ->latest()
            ->get();

        // Activity & KYC Histories
        $activityLogs = $canViewActivity
            ? $member->activityLogs()->with('user')->latest()->paginate(10, ['*'], 'activity_page')->withQueryString()
            : null;
        $kycHistories = $canViewSensitive
            ? $member->kycHistories()->with('changedBy')->latest('changed_at')->paginate(10, ['*'], 'kyc_page')->withQueryString()
            : null;

        return view('admin.mlm.members.show', compact(
            'member',
            'placementLevel',
            'directReferrals',
            'placementSlots',
            'totalPv',
            'totalIncome',
            'paidIncome',
            'pendingIncome',
            'calculations',
            'incomeLedgers',
            'payoutLines',
            'cashbacks',
            'activityLogs',
            'kycHistories',
            'canViewSensitive',
            'canViewActivity'
        ));
    }

    public function edit(Member $member): View
    {
        $member->load(['user', 'sponsor.user']);

        return view('admin.mlm.members.edit', [
            ...$this->formData($member),
            'canViewSensitive' => $this->can('mlm.kyc.view'),
        ]);
    }

    public function update(UpdateMlmMemberRequest $request, Member $member): RedirectResponse
    {
        $this->rejectStageThreePlacementFields($request, $member);
        $this->guardKycSubmission($request);

        $member = $this->memberService->update(
            $member,
            $request->validated(),
            $request->user(),
            $request->file('profile_photo'),
            $request->file('cancelled_cheque')
        );

        return redirect()->route('admin.mlm.members.show', $member)
            ->with('success', 'Member updated successfully.');
    }

    public function destroy(Request $request, Member $member): RedirectResponse|JsonResponse
    {
        /** @var User $admin */
        $admin = $request->user();

        // Strict Security: Only Super Admin can delete MLM members / root leaders
        if (! $admin || ! $admin->isSuperAdmin()) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access Denied: Only Super Admin has authority to delete MLM members.',
                ], 403);
            }

            abort(403, 'Access Denied: Only Super Admin has authority to delete MLM members.');
        }

        $deleteUser = $request->boolean('delete_user', false);

        $result = $this->memberService->delete($member, $admin, $deleteUser);

        $msg = "Member {$result['name']} ({$result['code']}) has been successfully deleted.";
        if ($result['is_root']) {
            $msg .= " Root Leader removed. Matrix structure safely re-anchored" . ($result['promoted_root'] ? " (New root: {$result['promoted_root']})." : ".");
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $msg,
                'data' => $result,
            ]);
        }

        return redirect()->to(admin_route('mlm.members.index'))->with('success', $msg);
    }

    public function toggleStatus(Request $request, Member $member): RedirectResponse
    {
        $requestedStatus = $request->input('status');
        $status = $requestedStatus ?: ($member->status === Member::STATUS_ACTIVE
            ? Member::STATUS_INACTIVE
            : Member::STATUS_ACTIVE);

        $request->validate([
            'status' => ['nullable', Rule::in(Member::STATUSES)],
        ]);

        if ($status === Member::STATUS_BLOCKED
            || ($member->status === Member::STATUS_BLOCKED && $status !== Member::STATUS_BLOCKED)) {
            abort_unless($this->can('mlm.block'), 403, 'Unauthorized action.');
        }

        $member = $this->memberService->setStatus($member, $status, $request->user());

        return back()->with('success', 'Member status updated successfully.');
    }

    public function kyc(Member $member): View
    {
        abort_unless($this->can('mlm.kyc.view'), 403, 'Unauthorized action.');

        $member->load(['user', 'sponsor.user', 'kycHistories.changedBy']);

        return view('admin.mlm.members.kyc', compact('member'));
    }

    public function reviewKyc(ReviewMlmKycRequest $request, Member $member): RedirectResponse
    {
        abort_unless($this->can('mlm.kyc.review'), 403, 'Unauthorized action.');

        $data = $request->validated();
        $this->kycService->review(
            $member,
            $data['kyc_status'],
            $data['kyc_rejection_reason'] ?? null,
            $request->user()
        );

        return back()->with('success', 'KYC status updated successfully.');
    }

    public function downloadCancelledCheque(Member $member): StreamedResponse
    {
        abort_unless($this->can('mlm.kyc.view'), 403, 'Unauthorized action.');

        if (! $member->cancelled_cheque_path || ! Storage::disk('local')->exists($member->cancelled_cheque_path)) {
            abort(404);
        }

        return Storage::disk('local')->download($member->cancelled_cheque_path);
    }

    private function formData(?Member $member = null): array
    {
        $existingCustomers = User::query()
            ->whereHas('role', fn ($query) => $query->where('name', 'Customer'))
            ->whereDoesntHave('mlmMember')
            ->orderBy('name')
            ->get();

        return [
            'member' => $member,
            'sponsors' => $this->sponsorOptions($member),
            'existingCustomers' => $existingCustomers,
            'canViewSensitive' => $this->can('mlm.kyc.view'),
        ];
    }

    private function sponsorOptions(?Member $exclude = null)
    {
        return Member::query()
            ->with('user')
            ->where('status', '!=', Member::STATUS_BLOCKED)
            ->when($exclude, fn ($query) => $query->whereKeyNot($exclude->id))
            ->orderBy('id')
            ->get();
    }

    private function rejectStageThreePlacementFields(Request $request, Member $member): void
    {
        if (! $request->hasAny(['sponsor_member_id', 'placement_parent_id', 'placement_position'])) {
            return;
        }

        $errors = [
            'placement_parent_id' => 'Placement fields belong to the Stage 3 placement workflow.',
            'placement_position' => 'Placement fields belong to the Stage 3 placement workflow.',
        ];

        if ((int) $request->input('sponsor_member_id') === $member->id) {
            $errors['sponsor_member_id'] = 'A member cannot be their own sponsor.';
        } else {
            $errors['sponsor_member_id'] = 'Sponsor changes require a separate audited action.';
        }

        throw ValidationException::withMessages($errors);
    }

    private function guardKycSubmission(Request $request): void
    {
        $kycFields = [
            'pan_number',
            'aadhaar_reference',
            'bank_account_holder_name',
            'bank_account_number',
            'ifsc_code',
            'bank_name',
            'bank_branch',
            'kyc_rejection_reason',
            'cancelled_cheque',
        ];
        $hasSensitivePayload = collect($kycFields)->contains(function (string $field) use ($request): bool {
            return $request->hasFile($field) || $request->filled($field);
        });

        if ($hasSensitivePayload && ! $this->can('mlm.kyc.view')) {
            abort(403, 'KYC data requires the KYC view permission.');
        }

        $kycStatus = $request->input('kyc_status');
        if ($kycStatus && $kycStatus !== Member::KYC_PENDING && ! $this->can('mlm.kyc.review')) {
            abort(403, 'KYC status changes require the KYC review permission.');
        }
    }

    private function can(string $permission): bool
    {
        $user = request()->user();

        if ($user?->hasPermission($permission)) {
            return true;
        }

        return ! in_array($permission, [
            'mlm.block',
            'mlm.kyc.view',
            'mlm.kyc.review',
            'mlm.activity.view',
        ], true) && (bool) $user?->hasPermission('mlm.manage');
    }

    public function manual(): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $path = public_path('docs/Aaradhya_MLM_Admin_User_Manual.pdf');
        if (! file_exists($path)) {
            $path = base_path('Aaradhya_MLM_Admin_User_Manual.pdf');
        }

        return response()->file($path, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="Aaradhya_MLM_Admin_User_Manual.pdf"',
        ]);
    }
}
