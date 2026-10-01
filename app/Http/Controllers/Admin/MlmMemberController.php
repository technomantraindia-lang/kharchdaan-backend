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

                $query->where(function ($memberQuery) use ($search): void {
                    $memberQuery->whereHas('user', function ($userQuery) use ($search): void {
                        $userQuery->where('mlm_member_id', 'like', "%{$search}%")
                            ->orWhere('name', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    })->orWhereHas('sponsor.user', function ($sponsorQuery) use ($search): void {
                        $sponsorQuery->where('mlm_member_id', 'like', "%{$search}%")
                            ->orWhere('name', 'like', "%{$search}%");
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
                ->whereNull('sponsor_member_id')
                ->orderBy('id')
                ->first()
                ?? Member::with(['user', 'sponsor.user'])->orderBy('id')->first();
        }

        $networkData = $root ? $this->treeService->getSponsorNetwork($root) : null;
        $allMembers = Member::with('user')->orderBy('id')->get();

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
                ->whereNull('placement_parent_id')
                ->orderBy('id')
                ->first()
                ?? Member::with(['user', 'sponsor.user', 'placementParent.user'])->orderBy('id')->first();
        }

        $genealogyData = $root ? $this->treeService->getGenealogy($root) : null;
        $roots = $this->treeService->roots();
        $allMembers = Member::with('user')->orderBy('id')->get();

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
        $root = $roots->firstWhere('id', $requestedRoot) ?? $roots->first();
        
        $initialRoot = null;
        if ($root) {
            $childrenData = $this->treeService->children($root);
            $initialRoot = [
                'member' => $childrenData['parent'],
                'slots' => $childrenData['slots'],
            ];
        }

        $rootPayloads = $roots->map(function (Member $rootMember): array {
            $childrenData = $this->treeService->children($rootMember);
            return [
                'member' => $childrenData['parent'],
                'slots' => $childrenData['slots'],
            ];
        })->values();

        return view('admin.mlm.tree', compact('roots', 'initialRoot', 'rootPayloads'));
    }

    public function treeChildren(Member $member): JsonResponse
    {
        return response()->json($this->treeService->children($member));
    }

    public function treeSearch(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['required', 'string', 'min:2', 'max:100'],
        ]);

        return response()->json([
            'data' => $this->treeService->search(trim($validated['q'])),
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
            'q' => ['required', 'string', 'min:2', 'max:100'],
        ]);

        return response()->json([
            'data' => $this->movementService->searchParents($member, trim($validated['q'])),
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

    public function move(MoveMlmMemberRequest $request, Member $member): RedirectResponse
    {
        abort_unless($this->can('mlm.move'), 403, 'Unauthorized action.');
        if (! $request->boolean('confirmed')) {
            throw ValidationException::withMessages([
                'confirmed' => 'Confirm the movement preview before saving.',
            ]);
        }

        $this->movementService->move($member, $request->validated(), $request->user());

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
}
