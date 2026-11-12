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
            'sponsor_root' => $this->memberPayload($member, includeSponsor: true, tree: 'sponsor'),
            'placement_root' => $this->memberPayload($member, includeSponsor: true, tree: 'placement'),
            'direct_referral_count' => $member->sponsoredMembers()->count(),
            'placement_network_count' => $placementMembers->count(),
            'visible_level' => $this->maxPlacementLevel($member, $placementMembers),
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
                ->map(fn (Member $member): array => $this->memberPayload($member, tree: 'sponsor'))
                ->values()
                ->all();

            return [
                'tree' => 'sponsor',
                'parent' => $this->memberPayload($parent, tree: 'sponsor'),
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
            'parent' => $this->memberPayload($parent, tree: 'placement'),
            'slots' => collect(Member::POSITIONS)
                ->map(fn (string $position): array => [
                    'position' => $position,
                    'position_label' => ucfirst($position),
                    'member' => isset($children[$position])
                        ? $this->memberPayload($children[$position], tree: 'placement')
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

    private function maxPlacementLevel(Member $root, Collection $members): int
    {
        $maximum = 0;

        foreach ($members as $member) {
            $level = 0;
            $currentId = $member->placement_parent_id;
            $visited = [];

            while ($currentId !== null && ! isset($visited[$currentId])) {
                if ($currentId === $root->id) {
                    $maximum = max($maximum, $level + 1);
                    break;
                }

                $visited[$currentId] = true;
                $parent = $members->firstWhere('id', $currentId);
                if (! $parent) {
                    break;
                }

                $level++;
                $currentId = $parent->placement_parent_id;
            }
        }

        return min($maximum, 19);
    }

    private function memberPayload(Member $member, bool $includeSponsor = false, ?string $tree = null): array
    {
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
            $payload['level'] = $this->placementLevel($member);
            $payload['level_label'] = 'Level '.$payload['level'];
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

    private function placementLevel(Member $member): int
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
