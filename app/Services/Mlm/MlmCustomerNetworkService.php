<?php

namespace App\Services\Mlm;

use App\Models\Member;
use App\Models\MlmIncomeLedger;
use App\Models\User;
use App\Support\MlmDecimal;
use Illuminate\Support\Collection;

class MlmCustomerNetworkService
{
    public function ownedMember(User $user): Member
    {
        abort_unless($user->isCustomer(), 403);

        $member = Member::query()
            ->with(['user', 'sponsor.user', 'placementParent.user'])
            ->where('user_id', $user->id)
            ->first();

        abort_unless($member, 403);

        return $member;
    }

    public function dashboard(User $user): array
    {
        $member = $this->ownedMember($user);
        $placementMembers = $this->descendants($member, 'placement');

        return [
            'member' => $this->memberPayload($member, includeSponsor: true),
            'sponsor_root' => $this->memberPayload($member, includeSponsor: true, tree: 'sponsor', viewerRoot: $member),
            'placement_root' => $this->memberPayload($member, includeSponsor: true, tree: 'placement', viewerRoot: $member),
            'direct_referral_count' => $member->sponsoredMembers()->count(),
            'placement_network_count' => $placementMembers->count(),
            'visible_level' => $this->maxRelativePlacementLevel($member, $placementMembers),
            'placement_assignment_note' => $member->placement_parent_id === null
                ? 'This member is not yet assigned on the 1:3 placement tree. Contact admin if placement is expected.'
                : null,
        ];
    }

    /**
     * Flat list of direct sponsored members for customer sponsor dashboard / API.
     */
    public function directReferrals(User $user): array
    {
        $member = $this->ownedMember($user);

        $referrals = Member::query()
            ->realDistributors()
            ->with(['user', 'sponsor.user'])
            ->where('sponsor_member_id', $member->id)
            ->orderByDesc('joined_at')
            ->orderByDesc('id')
            ->get();

        return [
            'sponsor' => [
                'id' => $member->id,
                'customer_id' => $member->customer_id,
                'name' => $member->user?->name,
                'phone' => $member->user?->phone,
                'status' => $member->status,
            ],
            'direct_referral_count' => $referrals->count(),
            'referrals' => $referrals->map(fn (Member $ref): array => [
                'id' => $ref->id,
                'customer_id' => $ref->customer_id,
                'name' => $ref->user?->name,
                'email' => $ref->user?->email,
                'phone' => $ref->user?->phone,
                'status' => $ref->status,
                'kyc_status' => $ref->kyc_status,
                'joined_at' => $ref->joined_at?->toDateString(),
                'joined_at_label' => $ref->joined_at?->format('d M Y'),
                'placement_position' => $ref->placement_position,
                'has_placement' => $ref->placement_parent_id !== null,
            ])->values()->all(),
        ];
    }

    public function children(User $user, Member $parent, string $tree): array
    {
        $root = $this->ownedMember($user);
        abort_unless(in_array($tree, ['sponsor', 'placement'], true), 422);
        $this->assertInOwnNetwork($root, $parent, $tree);

        if ($tree === 'sponsor') {
            $children = Member::query()
                ->with('user')
                ->withCount('sponsoredMembers')
                ->where('sponsor_member_id', $parent->id)
                ->orderBy('id')
                ->get()
                ->map(fn (Member $member): array => $this->memberPayload($member, tree: 'sponsor', viewerRoot: $root))
                ->values()
                ->all();

            return [
                'tree' => 'sponsor',
                'parent' => $this->memberPayload($parent, tree: 'sponsor', viewerRoot: $root),
                'children' => $children,
            ];
        }

        $children = Member::query()
            ->with('user')
            ->withCount('placementChildren')
            ->where('placement_parent_id', $parent->id)
            ->whereIn('placement_position', Member::POSITIONS)
            ->get()
            ->keyBy('placement_position');

        return [
            'tree' => 'placement',
            'parent' => $this->memberPayload($parent, tree: 'placement', viewerRoot: $root),
            'slots' => collect(Member::POSITIONS)
                ->map(fn (string $position): array => [
                    'position' => $position,
                    'position_label' => ucfirst($position),
                    'member' => isset($children[$position])
                        ? $this->memberPayload($children[$position], tree: 'placement', viewerRoot: $root)
                        : null,
                ])
                ->all(),
        ];
    }

    public function levels(User $user): array
    {
        $member = $this->ownedMember($user);
        $ledgers = MlmIncomeLedger::query()
            ->where('member_id', $member->id)
            ->whereBetween('level', [0, 19])
            ->where('status', '!=', MlmIncomeLedger::STATUS_FAILED)
            ->orderBy('level')
            ->get();

        $byLevel = $ledgers->groupBy('level');

        return [
            'member' => $this->memberPayload($member, includeSponsor: true),
            'levels' => collect(range(0, 19))->map(function (int $level) use ($byLevel): array {
                /** @var Collection<int, MlmIncomeLedger> $entries */
                $entries = $byLevel->get($level, collect());

                if ($entries->isEmpty()) {
                    return [
                        'level' => $level,
                        'label' => 'Level '.$level,
                        'meaning' => $this->levelMeaning($level),
                        'calculated' => false,
                        'member_count' => null,
                        'pv' => null,
                        'income' => null,
                        'status' => 'Not yet calculated',
                    ];
                }

                return [
                    'level' => $level,
                    'label' => 'Level '.$level,
                    'meaning' => $this->levelMeaning($level),
                    'calculated' => true,
                    'member_count' => $entries->pluck('purchasing_member_id')->filter()->unique()->count(),
                    'pv' => $entries->reduce(
                        fn (string $total, MlmIncomeLedger $entry): string => MlmDecimal::add($total, (string) $entry->pv),
                        MlmDecimal::normalize('0')
                    ),
                    'income' => $entries->reduce(
                        fn (string $total, MlmIncomeLedger $entry): string => MlmDecimal::add($total, (string) $entry->calculated_amount),
                        MlmDecimal::normalize('0')
                    ),
                    'status' => 'Calculated',
                ];
            })->all(),
        ];
    }

    private function assertInOwnNetwork(Member $root, Member $candidate, string $tree): void
    {
        if ($candidate->id === $root->id) {
            return;
        }

        $foreignKey = $tree === 'sponsor' ? 'sponsor_member_id' : 'placement_parent_id';
        $currentId = $candidate->id;
        $visited = [];

        while ($currentId !== null && ! isset($visited[$currentId])) {
            if ($currentId === $root->id) {
                return;
            }

            $visited[$currentId] = true;
            $currentId = Member::query()->whereKey($currentId)->value($foreignKey);
        }

        abort(403);
    }

    private function descendants(Member $root, string $tree): Collection
    {
        $foreignKey = $tree === 'sponsor' ? 'sponsor_member_id' : 'placement_parent_id';
        $frontier = collect([$root->id]);
        $members = collect();

        while ($frontier->isNotEmpty()) {
            $children = Member::query()
                ->whereIn($foreignKey, $frontier->all())
                ->orderBy('id')
                ->get();

            if ($children->isEmpty()) {
                break;
            }

            $members = $members->merge($children);
            $frontier = $children->pluck('id')->values();
        }

        return $members;
    }

    /**
     * Deepest viewer-relative placement level in the logged-in member's subtree.
     * Self = Level 1; direct children = Level 2; etc.
     */
    private function maxRelativePlacementLevel(Member $root, Collection $members): int
    {
        $maximum = 1;

        foreach ($members as $member) {
            $relative = $this->relativePlacementLevel($member, $root);
            if ($relative !== null) {
                $maximum = max($maximum, $relative);
            }
        }

        return min($maximum, 20);
    }

    private function memberPayload(
        Member $member,
        bool $includeSponsor = false,
        ?string $tree = null,
        ?Member $viewerRoot = null,
    ): array {
        $member->loadMissing('user');

        $payload = [
            'id' => $member->id,
            'customer_id' => $member->customer_id,
            'name' => $member->user?->name ?? 'Unnamed member',
            'status' => $member->status,
            'status_label' => ucfirst((string) $member->status),
            'joined_at' => $member->joined_at?->toDateString(),
            'position' => $member->placement_position,
            'position_label' => $member->placement_position ? ucfirst($member->placement_position) : 'Root',
            'direct_referral_count' => $member->sponsored_members_count
                ?? $member->sponsoredMembers()->count(),
            'has_children' => $tree === 'sponsor'
                ? (bool) ($member->sponsored_members_count ?? $member->sponsoredMembers()->exists())
                : (bool) ($member->placement_children_count ?? $member->placementChildren()->exists()),
        ];

        if ($tree === 'placement') {
            // Global depth is computed for reference only — never written back to the DB.
            $globalLevel = $this->globalPlacementLevel($member);
            $payload['global_level'] = $globalLevel;

            // Personal dashboard always uses viewer-relative levels (self = Level 1).
            // level_label is numeric only; the UI prefixes "Level ".
            if ($viewerRoot !== null) {
                $relative = $this->relativePlacementLevel($member, $viewerRoot);
                abort_if($relative === null, 403);

                $payload['level'] = $relative;
                $payload['level_label'] = (string) $relative;
            } else {
                $payload['level'] = $globalLevel;
                $payload['level_label'] = (string) $globalLevel;
            }
        }

        if ($includeSponsor && $member->sponsor) {
            $payload['sponsor'] = [
                'customer_id' => $member->sponsor->customer_id,
                'name' => $member->sponsor->user?->name ?? 'Unnamed member',
            ];
        } else {
            $payload['sponsor'] = null;
        }

        return $payload;
    }

    /**
     * Global placement depth from the matrix root (unchanged admin semantics).
     * Root member = 0; each placement hop down increments by 1.
     */
    private function globalPlacementLevel(Member $member): int
    {
        $level = 0;
        $currentId = $member->placement_parent_id;
        $visited = [];

        while ($currentId !== null && ! isset($visited[$currentId]) && $level < 20) {
            $visited[$currentId] = true;
            $currentId = Member::query()->whereKey($currentId)->value('placement_parent_id');
            $level++;
        }

        return min($level, 19);
    }

    /**
     * Viewer-relative display level: displayLevel = depthFromLoggedInMember + 1.
     * Logged-in member = Level 1; direct placement children = Level 2; etc.
     * Returns null when $member is not in $viewerRoot's placement subtree.
     */
    private function relativePlacementLevel(Member $member, Member $viewerRoot): ?int
    {
        if ($member->id === $viewerRoot->id) {
            return 1;
        }

        $depthFromViewer = 0;
        $currentId = $member->placement_parent_id;
        $visited = [];

        while ($currentId !== null && ! isset($visited[$currentId]) && $depthFromViewer < 20) {
            $depthFromViewer++;

            if ($currentId === $viewerRoot->id) {
                return $depthFromViewer + 1;
            }

            $visited[$currentId] = true;
            $currentId = Member::query()->whereKey($currentId)->value('placement_parent_id');
        }

        return null;
    }

    private function levelMeaning(int $level): string
    {
        if ($level === 0) {
            return 'Own Purchase';
        }

        $ordinals = [
            1 => 'First', 2 => 'Second', 3 => 'Third', 4 => 'Fourth', 5 => 'Fifth',
            6 => 'Sixth', 7 => 'Seventh', 8 => 'Eighth', 9 => 'Ninth', 10 => 'Tenth',
            11 => 'Eleventh', 12 => 'Twelfth', 13 => 'Thirteenth', 14 => 'Fourteenth',
            15 => 'Fifteenth', 16 => 'Sixteenth', 17 => 'Seventeenth', 18 => 'Eighteenth',
            19 => 'Nineteenth',
        ];

        return ($ordinals[$level] ?? 'Placement').' placement level';
    }
}
