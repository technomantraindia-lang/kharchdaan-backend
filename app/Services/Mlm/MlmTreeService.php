<?php

namespace App\Services\Mlm;

use App\Models\Member;
use App\Models\MlmCalculationRule;
use App\Models\MlmIncomeLedger;
use App\Support\MlmDecimal;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class MlmTreeService
{
    public function roots(): Collection
    {
        return Member::query()
            ->with(['user', 'sponsor.user', 'placementParent.user'])
            ->withCount('placementChildren')
            ->whereNull('placement_parent_id')
            ->orderBy('id')
            ->get();
    }

    public function children(Member $parent): array
    {
        $children = Member::query()
            ->with(['user', 'sponsor.user', 'placementParent.user'])
            ->withCount('placementChildren')
            ->where('placement_parent_id', $parent->id)
            ->whereIn('placement_position', Member::POSITIONS)
            ->get()
            ->keyBy('placement_position');

        return [
            'parent' => $this->memberPayload($parent),
            'slots' => collect(Member::POSITIONS)->map(function (string $position) use ($children): array {
                $child = $children->get($position);

                return [
                    'position' => $position,
                    'position_label' => ucfirst($position),
                    'member' => $child ? $this->memberPayload($child) : null,
                ];
            })->values()->all(),
        ];
    }

    public function memberPayload(Member $member): array
    {
        $hasChildren = isset($member->placement_children_count)
            ? (int) $member->placement_children_count > 0
            : Member::query()->where('placement_parent_id', $member->id)->exists();
        $level = $this->level($member);

        $totalIncome = (float) MlmIncomeLedger::query()
            ->where('member_id', $member->id)
            ->where('status', '!=', MlmIncomeLedger::STATUS_FAILED)
            ->where('status', '!=', MlmIncomeLedger::STATUS_REVERSED)
            ->sum('calculated_amount');

        return [
            'id' => (int) $member->id,
            'customer_id' => $member->customer_id,
            'name' => $member->user?->name ?? 'Unnamed member',
            'email' => $member->user?->email,
            'phone' => $member->user?->phone,
            'status' => $member->status,
            'status_label' => ucfirst($member->status),
            'kyc_status' => $member->kyc_status,
            'position' => $member->placement_position,
            'position_label' => $member->placement_position
                ? ucfirst($member->placement_position)
                : 'Root',
            'level' => $level,
            'level_label' => $level > 19 ? '20+' : (string) $level,
            'total_income' => (int) round($totalIncome),
            'total_income_formatted' => '₹'.number_format(round($totalIncome)),
            'has_children' => $hasChildren,
            'sponsor' => $member->sponsor ? [
                'id' => $member->sponsor->id,
                'customer_id' => $member->sponsor->customer_id,
                'name' => $member->sponsor->user?->name ?? 'Unknown',
            ] : null,
            'placement_parent' => $member->placementParent ? [
                'id' => $member->placementParent->id,
                'customer_id' => $member->placementParent->customer_id,
                'name' => $member->placementParent->user?->name ?? 'Unknown',
            ] : null,
            'details_url' => admin_route('mlm.members.show', $member),
            'move_url' => admin_route('mlm.tree.move.form', $member),
        ];
    }

    public function search(string $term): array
    {
        return Member::query()
            ->with(['user', 'sponsor.user', 'placementParent.user'])
            ->whereHas('user', function ($query) use ($term): void {
                $query->where('mlm_member_id', 'like', "%{$term}%")
                    ->orWhere('name', 'like', "%{$term}%")
                    ->orWhere('phone', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%");
            })
            ->orderBy('id')
            ->limit(25)
            ->get()
            ->map(function (Member $member): ?array {
                $path = $this->placementPath($member);

                if ($path === null) {
                    return null;
                }

                return [
                    'member' => $this->memberPayload($member),
                    'root_id' => $path[0],
                    'path' => $path,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    public function level(Member $member): int
    {
        $path = $this->placementPath($member);

        return $path === null ? 0 : max(count($path) - 1, 0);
    }

    public function placementPath(Member $member): ?array
    {
        $path = [];
        $visited = [];
        $current = $member;

        while ($current) {
            if (isset($visited[$current->id])) {
                return null;
            }

            $visited[$current->id] = true;
            array_unshift($path, (int) $current->id);

            if ($current->placement_parent_id === null) {
                return $path;
            }

            if (count($path) > 20) {
                return $path;
            }

            $current = Member::query()->find($current->placement_parent_id);
        }

        return null;
    }

    /**
     * Compute comprehensive Sponsor Network data for a root member.
     */
    public function getSponsorNetwork(Member $root): array
    {
        $root->loadMissing(['user', 'sponsor.user']);

        // Recursively find all sponsor downline IDs
        $downlineIds = $this->sponsorDownlineIds($root->id);

        $directReferrals = Member::query()
            ->with(['user', 'sponsor.user'])
            ->withCount('sponsoredMembers')
            ->where('sponsor_member_id', $root->id)
            ->orderBy('id')
            ->get();

        $activeDirectCount = $directReferrals->where('status', Member::STATUS_ACTIVE)->count();

        $totalDownlineCount = count($downlineIds);
        $activeDownlineCount = $totalDownlineCount > 0
            ? Member::whereIn('id', $downlineIds)->where('status', Member::STATUS_ACTIVE)->count()
            : 0;

        // Calculate Network PV & Income from downline
        $networkPv = $totalDownlineCount > 0
            ? (float) MlmIncomeLedger::whereIn('purchasing_member_id', array_merge([$root->id], $downlineIds))->where('status', '!=', MlmIncomeLedger::STATUS_FAILED)->sum('pv')
            : (float) MlmIncomeLedger::where('purchasing_member_id', $root->id)->where('status', '!=', MlmIncomeLedger::STATUS_FAILED)->sum('pv');

        $networkIncome = $totalDownlineCount > 0
            ? (float) MlmIncomeLedger::whereIn('member_id', array_merge([$root->id], $downlineIds))->where('status', '!=', MlmIncomeLedger::STATUS_FAILED)->sum('calculated_amount')
            : (float) MlmIncomeLedger::where('member_id', $root->id)->where('status', '!=', MlmIncomeLedger::STATUS_FAILED)->sum('calculated_amount');

        $rootPersonalPv = (float) MlmIncomeLedger::where('purchasing_member_id', $root->id)->where('status', '!=', MlmIncomeLedger::STATUS_FAILED)->sum('pv');
        $rootPersonalIncome = (float) MlmIncomeLedger::where('member_id', $root->id)->where('status', '!=', MlmIncomeLedger::STATUS_FAILED)->sum('calculated_amount');

        // Build 2-level nested tree for visual sponsor explorer
        $directReferralsPayload = $directReferrals->map(function ($child) {
            $childDownlineIds = $this->sponsorDownlineIds($child->id);
            $childPv = count($childDownlineIds) > 0
                ? (float) MlmIncomeLedger::whereIn('purchasing_member_id', array_merge([$child->id], $childDownlineIds))->where('status', '!=', MlmIncomeLedger::STATUS_FAILED)->sum('pv')
                : (float) MlmIncomeLedger::where('purchasing_member_id', $child->id)->where('status', '!=', MlmIncomeLedger::STATUS_FAILED)->sum('pv');

            return [
                'member' => $this->memberPayload($child),
                'direct_referrals_count' => $child->sponsored_members_count,
                'total_downline_count' => count($childDownlineIds),
                'active_downline_count' => count($childDownlineIds) > 0 ? Member::whereIn('id', $childDownlineIds)->where('status', Member::STATUS_ACTIVE)->count() : 0,
                'network_pv' => round($childPv, 2),
            ];
        })->all();

        return [
            'root' => $this->memberPayload($root),
            'direct_referrals_count' => $directReferrals->count(),
            'active_direct_count' => $activeDirectCount,
            'total_downline_count' => $totalDownlineCount,
            'active_downline_count' => $activeDownlineCount,
            'network_pv' => round($networkPv, 2),
            'network_income' => (int) round($networkIncome),
            'personal_pv' => round($rootPersonalPv, 2),
            'personal_income' => (int) round($rootPersonalIncome),
            'direct_referrals' => $directReferralsPayload,
        ];
    }

    /**
     * Compute full placement Genealogy Level-by-Level (0 to 19).
     */
    public function getGenealogy(Member $root, int $maxDepth = 19): array
    {
        $root->loadMissing(['user', 'sponsor.user', 'placementParent.user']);

        // Load all placement members and build parent->children adjacency map
        $allMembers = Member::query()->with(['user', 'sponsor.user', 'placementParent.user'])->get()->keyBy('id');
        
        $childrenMap = [];
        foreach ($allMembers as $m) {
            if ($m->placement_parent_id !== null) {
                $childrenMap[$m->placement_parent_id][] = $m;
            }
        }

        // Traverse BFS level by level up to $maxDepth
        $levels = [];
        $currentLevelMembers = collect([$root]);
        $visited = [$root->id => true];

        for ($depth = 0; $depth <= $maxDepth; $depth++) {
            if ($currentLevelMembers->isEmpty()) {
                break;
            }

            $levelItems = [];
            $nextLevelMembers = collect();

            foreach ($currentLevelMembers as $member) {
                $hasChildren = isset($childrenMap[$member->id]) && count($childrenMap[$member->id]) > 0;
                
                $levelItems[] = [
                    'id' => $member->id,
                    'customer_id' => $member->customer_id,
                    'name' => $member->user?->name ?? 'Unnamed',
                    'email' => $member->user?->email,
                    'phone' => $member->user?->phone,
                    'level' => $depth,
                    'placement_position' => $member->placement_position ?? 'root',
                    'sponsor_name' => $member->sponsor?->user?->name ?? 'None',
                    'sponsor_id' => $member->sponsor?->customer_id ?? '-',
                    'placement_parent_name' => $member->placementParent?->user?->name ?? 'None',
                    'status' => $member->status,
                    'kyc_status' => $member->kyc_status,
                    'has_children' => $hasChildren,
                    'children_count' => isset($childrenMap[$member->id]) ? count($childrenMap[$member->id]) : 0,
                    'details_url' => admin_route('mlm.members.show', $member),
                ];

                if (isset($childrenMap[$member->id])) {
                    foreach ($childrenMap[$member->id] as $child) {
                        if (!isset($visited[$child->id])) {
                            $visited[$child->id] = true;
                            $nextLevelMembers->push($child);
                        }
                    }
                }
            }

            $levels[$depth] = [
                'level' => $depth,
                'label' => 'Level ' . $depth,
                'tier' => $depth <= 7 ? 'High PV Tier (13.5)' : 'Low PV Tier (0.75)',
                'count' => count($levelItems),
                'members' => $levelItems,
            ];

            $currentLevelMembers = $nextLevelMembers;
        }

        return [
            'root' => $this->memberPayload($root),
            'total_levels_found' => count($levels),
            'total_nodes' => count($visited),
            'levels' => $levels,
        ];
    }

    /**
     * Compute Level Management reference & live database statistics for Levels 0-19.
     */
    public function getLevelStatistics(): array
    {
        // 1. Placement Level distribution from database
        $parentMap = Member::query()->pluck('placement_parent_id', 'id')->all();
        $statuses = Member::query()->pluck('status', 'id')->all();

        $levelCounts = array_fill(0, 20, 0);
        $levelActiveCounts = array_fill(0, 20, 0);

        foreach ($parentMap as $id => $parentId) {
            $lvl = 0;
            $curr = $parentId;
            $visited = [$id => true];
            while ($curr !== null && !isset($visited[$curr]) && $lvl < 20) {
                $lvl++;
                $visited[$curr] = true;
                $curr = $parentMap[$curr] ?? null;
            }
            $finalLevel = min($lvl, 19);
            $levelCounts[$finalLevel]++;
            if (($statuses[$id] ?? null) === Member::STATUS_ACTIVE) {
                $levelActiveCounts[$finalLevel]++;
            }
        }

        // 2. Financial PV & Income totals by Level
        $ledgerStats = MlmIncomeLedger::query()
            ->where('status', '!=', MlmIncomeLedger::STATUS_FAILED)
            ->whereBetween('level', [0, 19])
            ->select('level', DB::raw('SUM(pv) as total_pv'), DB::raw('SUM(calculated_amount) as total_income'))
            ->groupBy('level')
            ->get()
            ->keyBy('level');

        $levels = [];
        for ($i = 0; $i < 20; $i++) {
            $isHighTier = $i <= 7;
            $pvFactor = $isHighTier ? 13.5 : 0.75;
            $pvRate = $pvFactor / 3000.0;
            $incomeRate = 0.20;

            // Example for ₹1000
            $examplePv = (1000 * $pvFactor) / 3000.0;
            $exampleIncome = $examplePv * $incomeRate;

            $lStat = $ledgerStats->get($i);

            $levels[] = [
                'level' => $i,
                'tier_name' => $isHighTier ? 'High PV Tier' : 'Low PV Tier',
                'pv_formula' => "({$pvFactor} × Eligible Amount) / 3000",
                'pv_rate' => $pvRate,
                'income_rate' => '20%',
                'income_rate_decimal' => $incomeRate,
                'example_amount' => 1000.0,
                'example_pv' => round($examplePv, 2),
                'example_income' => (int) round($exampleIncome),
                'member_count' => $levelCounts[$i],
                'active_member_count' => $levelActiveCounts[$i],
                'db_total_pv' => $lStat ? round((float) $lStat->total_pv, 2) : 0.0,
                'db_total_income' => $lStat ? (int) round((float) $lStat->total_income) : 0,
            ];
        }

        return $levels;
    }

    /**
     * Simulate level-by-level income for an eligible transaction amount.
     */
    public function simulateLevelIncome(float $amount, ?Member $member = null): array
    {
        $breakdown = [];
        $totalPv = 0.0;
        $totalIncome = 0.0;

        for ($lvl = 0; $lvl < 20; $lvl++) {
            $isHighTier = $lvl <= 7;
            $pvFactor = $isHighTier ? 13.5 : 0.75;
            $pv = ($amount * $pvFactor) / 3000.0;
            $income = $pv * 0.20;

            $totalPv += $pv;
            $totalIncome += $income;

            $breakdown[] = [
                'level' => $lvl,
                'tier' => $isHighTier ? 'High PV (13.5)' : 'Low PV (0.75)',
                'factor' => $pvFactor,
                'pv' => round($pv, 2),
                'income_rate' => '20%',
                'income' => (int) round($income),
            ];
        }

        return [
            'amount' => $amount,
            'member' => $member ? $this->memberPayload($member) : null,
            'breakdown' => $breakdown,
            'total_pv' => round($totalPv, 2),
            'total_income' => (int) round($totalIncome),
        ];
    }

    private function sponsorDownlineIds(int $rootId): array
    {
        $frontier = [$rootId];
        $allDownline = [];
        $visited = [$rootId => true];

        while (!empty($frontier)) {
            $childrenIds = Member::query()
                ->whereIn('sponsor_member_id', $frontier)
                ->pluck('id')
                ->all();

            $nextFrontier = [];
            foreach ($childrenIds as $cid) {
                if (!isset($visited[$cid])) {
                    $visited[$cid] = true;
                    $allDownline[] = (int) $cid;
                    $nextFrontier[] = (int) $cid;
                }
            }

            $frontier = $nextFrontier;
        }

        return $allDownline;
    }
}
