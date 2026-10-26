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
    protected ?array $downlineCountsCache = null;

    public function getDownlineCounts(): array
    {
        if ($this->downlineCountsCache !== null) {
            return $this->downlineCountsCache;
        }

        $all = Member::query()->select('id', 'placement_parent_id')->get();
        $byParent = [];
        foreach ($all as $m) {
            $pid = $m->placement_parent_id ?? 0;
            $byParent[$pid][] = $m->id;
        }

        $counts = [];
        $countTree = function ($id) use (&$countTree, &$byParent, &$counts): int {
            if (isset($counts[$id])) {
                return $counts[$id];
            }
            $children = $byParent[$id] ?? [];
            $total = count($children);
            foreach ($children as $cid) {
                $total += $countTree($cid);
            }
            $counts[$id] = $total;
            return $total;
        };

        foreach ($all as $m) {
            $countTree($m->id);
        }

        return $this->downlineCountsCache = $counts;
    }

    public function getDownlineCount(int $memberId): int
    {
        $counts = $this->getDownlineCounts();
        return $counts[$memberId] ?? 0;
    }

    public function leaderOptions(): array
    {
        $downlines = $this->getDownlineCounts();

        $mPlaceholderCodes = [
            'C25070003','C25070004','C25070005','C25070006',
            'C25070007','C25070008','C25070009','C25070010',
            'C25070011','C25070012','C25070013','C25070014','C25070015',
        ];

        // 1. Executive Top ID & Direct Pillars (Real Leaders)
        $pillarCodes = [
            'C22020008', // Nimesh Jagirdar (Top ID & Founder)
            'C22010003', // Janakben Bihola (Left Pillar)
            'C22020001', // Niruben Zala (Middle Pillar)
            'C22030018', // Gaurav Agarwal (Right Pillar)
        ];

        $companyMembers = Member::query()
            ->with(['user.addresses', 'sponsor.user', 'placementParent.user'])
            ->whereHas('user', function ($q) use ($pillarCodes) {
                $q->whereIn('mlm_member_id', $pillarCodes);
            })
            ->get()
            ->sortBy(function (Member $m) {
                return match ($m->customer_id) {
                    'C22020008' => 0,
                    'C22010003' => 1,
                    'C22020001' => 2,
                    'C22030018' => 3,
                    default => 99,
                };
            })
            ->values();

        // 2. Top Distributor Leaders (All real human distributors)
        $topDistributors = Member::query()
            ->with(['user.addresses', 'sponsor.user', 'placementParent.user'])
            ->whereHas('user', function ($q) use ($mPlaceholderCodes, $pillarCodes) {
                $q->whereNotIn('mlm_member_id', array_merge($mPlaceholderCodes, ['C22020008']));
            })
            ->get()
            ->sortByDesc(fn (Member $m) => $downlines[$m->id] ?? 0)
            ->values();

        // Top 8 for quick toolbar pills
        $quickPills = $topDistributors->take(8)->map(fn ($m) => [
            'id' => $m->id,
            'name' => $m->user?->name ?? 'Distributor',
            'customer_id' => $m->customer_id,
            'phone' => $m->user?->phone,
            'downlines' => $downlines[$m->id] ?? 0,
        ])->all();

        return [
            'company' => $companyMembers->map(function ($m) use ($downlines) {
                $cid = $m->customer_id;
                $name = $m->user?->name;
                $dl = $downlines[$m->id] ?? 0;
                $label = match ($cid) {
                    'C22020008' => "{$name} — Top ID & Founder (Aaradhya Gruh Udyog - {$dl} Downlines)",
                    'C22010003' => "{$name} — Left Pillar Leader ({$dl} Downlines)",
                    'C22020001' => "{$name} — Middle Pillar Leader ({$dl} Downlines)",
                    'C22030018' => "{$name} — Right Pillar Leader ({$dl} Downlines)",
                    default => "{$name} — Master Distributor ({$dl} Downlines)",
                };
                return [
                    'id' => $m->id,
                    'name' => $name,
                    'customer_id' => $cid,
                    'downlines' => $dl,
                    'label' => $label,
                ];
            })->all(),
            'leaders' => $topDistributors->take(30)->map(fn ($m) => [
                'id' => $m->id,
                'name' => $m->user?->name,
                'customer_id' => $m->customer_id,
                'mobile' => $m->user?->phone,
                'downlines' => $downlines[$m->id] ?? 0,
                'label' => "{$m->user?->name} ({$m->customer_id}) — " . ($downlines[$m->id] ?? 0) . " Downlines",
            ])->all(),
            'quick_pills' => $quickPills,
        ];
    }

    public function roots(): Collection
    {
        return Member::query()
            ->with(['user.addresses', 'sponsor.user', 'placementParent.user', 'appreciationMember.user'])
            ->withCount('placementChildren')
            ->whereNull('placement_parent_id')
            ->where('status', 'active')
            ->orderByRaw("CASE WHEN EXISTS (SELECT 1 FROM users WHERE users.id = mlm_members.user_id AND users.mlm_member_id = 'C22020008') THEN 0 ELSE 1 END")
            ->orderBy('id')
            ->get();
    }

    public function children(Member $parent): array
    {
        $children = Member::query()
            ->with(['user.addresses', 'sponsor.user', 'placementParent.user', 'appreciationMember.user'])
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
        $downlineCount = $this->getDownlineCount($member->id);

        $totalIncome = (float) MlmIncomeLedger::query()
            ->where('member_id', $member->id)
            ->where('status', '!=', MlmIncomeLedger::STATUS_FAILED)
            ->where('status', '!=', MlmIncomeLedger::STATUS_REVERSED)
            ->sum('calculated_amount');

        // Check if member is boosted by appreciation idle slots (Double Income & Dual Placement)
        $boostedSlots = Member::query()
            ->with(['placementParent.user'])
            ->where('appreciation_member_id', $member->id)
            ->get();
        $boostedIncome = 0;
        $boostedLabels = [];
        $promotionsHeld = [];
        foreach ($boostedSlots as $slot) {
            $boostedLabels[] = $slot->idle_slot_label ?? $slot->customer_id;
            $boostedIncome += (float) MlmIncomeLedger::query()
                ->where('member_id', $slot->id)
                ->where('status', '!=', MlmIncomeLedger::STATUS_FAILED)
                ->where('status', '!=', MlmIncomeLedger::STATUS_REVERSED)
                ->sum('calculated_amount');

            $isSlotExpired = $slot->appreciation_expires_at && $slot->appreciation_expires_at->isPast();
            $now = now();
            $diffDays = $slot->appreciation_expires_at ? (int) ceil($now->diffInDays($slot->appreciation_expires_at, false)) : null;

            $promotionsHeld[] = [
                'slot_id' => $slot->id,
                'slot_customer_id' => $slot->customer_id,
                'slot_label' => $slot->idle_slot_label ?? 'Idle Slot',
                'slot_position' => $slot->placement_position,
                'slot_level' => $this->level($slot),
                'slot_path' => $this->placementPath($slot),
                'assigned_at' => $slot->appreciation_assigned_at?->format('d M Y, h:i A'),
                'expires_at' => $slot->appreciation_expires_at?->format('d M Y, h:i A'),
                'expires_at_iso' => $slot->appreciation_expires_at?->toIso8601String(),
                'days_remaining' => $diffDays !== null ? max(0, $diffDays) : null,
                'is_expired' => $isSlotExpired,
                'is_indefinite' => empty($slot->appreciation_expires_at),
                'note' => $slot->appreciation_note,
            ];
        }
        $combinedIncome = $totalIncome + $boostedIncome;

        $cid = $member->customer_id;
        $isTopRoot = ($cid === 'C22020008');
        $idleCodes = [
            'C25070004' => 'Idle - 2',
            'C25070005' => 'Idle - 3',
            'C25070006' => 'Idle - 4',
            'C25070007' => 'Idle - 5',
            'C25070008' => 'Idle - 6',
            'C25070009' => 'Idle - 7',
            'C25070010' => 'Idle - 8',
            'C25070011' => 'Idle - 9',
            'C25070012' => 'Idle - 10',
            'C25070013' => 'Idle - 11',
            'C25070014' => 'Idle - 12',
            'C25070015' => 'Idle - 13',
        ];
        $isIdleSlot = (bool) ($member->is_idle_slot || isset($idleCodes[$cid]));
        $idleLabel = $member->idle_slot_label ?? ($idleCodes[$cid] ?? null);

        $companyLabel = match (true) {
            $isTopRoot => 'Top ID: 1 - Nimesh Jagirdar (Master Root)',
            $isIdleSlot => ($idleLabel ?? 'Idle Position') . ' (Appreciation Slot)',
            default => null,
        };

        $appreciationMember = null;
        $promotedInfo = null;
        if ($member->appreciation_member_id) {
            $appMember = $member->appreciationMember ?? Member::with(['user.addresses'])->find($member->appreciation_member_id);
            if ($appMember) {
                $isExpired = $member->appreciation_expires_at && $member->appreciation_expires_at->isPast();
                $now = now();
                $diffDays = $member->appreciation_expires_at ? (int) ceil($now->diffInDays($member->appreciation_expires_at, false)) : null;
                $diffHours = $member->appreciation_expires_at ? (int) ceil($now->diffInHours($member->appreciation_expires_at, false)) : null;

                $origLevel = $this->level($appMember);
                $origPath = $this->placementPath($appMember);

                $promotedInfo = [
                    'id' => $appMember->id,
                    'customer_id' => $appMember->customer_id,
                    'name' => $appMember->user?->name ?? 'Distributor',
                    'phone' => $appMember->user?->phone ?? '',
                    'email' => $appMember->user?->email ?? '',
                    'original_level' => $origLevel,
                    'original_level_label' => $origLevel > 19 ? '20+' : (string) $origLevel,
                    'original_path' => $origPath,
                    'status' => $appMember->status,
                    'kyc_status' => $appMember->kyc_status,
                    'assigned_at' => $member->appreciation_assigned_at?->format('d M Y, h:i A'),
                    'expires_at' => $member->appreciation_expires_at?->format('d M Y, h:i A'),
                    'expires_at_iso' => $member->appreciation_expires_at?->toIso8601String(),
                    'days_remaining' => $diffDays !== null ? max(0, $diffDays) : null,
                    'hours_remaining' => $diffHours !== null ? max(0, $diffHours) : null,
                    'is_expired' => $isExpired,
                    'is_indefinite' => empty($member->appreciation_expires_at),
                    'note' => $member->appreciation_note,
                    'idle_slot_label' => $idleLabel,
                    'idle_slot_code' => $cid,
                ];

                $appreciationMember = $promotedInfo;
            }
        }

        $address = $member->user?->addresses?->first();

        // When a member is promoted to this idle slot, show the promoted member's identity at this card!
        $displayName = $isTopRoot
            ? '1 - Nimesh Jagirdar'
            : ($isIdleSlot
                ? ($promotedInfo ? $promotedInfo['name'] : ($idleLabel ?? $member->user?->name))
                : ($member->user?->name ?? 'Unnamed member'));

        $displayPhone = ($isIdleSlot && $promotedInfo)
            ? $promotedInfo['phone']
            : $member->user?->phone;

        $displayEmail = ($isIdleSlot && $promotedInfo)
            ? $promotedInfo['email']
            : $member->user?->email;

        return [
            'id' => (int) $member->id,
            'customer_id' => ($isIdleSlot && $promotedInfo) ? $promotedInfo['customer_id'] : $member->customer_id,
            'slot_customer_id' => $member->customer_id,
            'name' => $displayName,
            'email' => $displayEmail,
            'phone' => $displayPhone,
            'city' => $address?->city ?? 'Ahmedabad',
            'state' => $address?->state ?? 'Gujarat',
            'area' => $address?->apt ?? $address?->area ?? '',
            'full_address' => $address?->address ?? '',
            'pincode' => $address?->zip ?? '',
            'is_company_node' => $isTopRoot || ($isIdleSlot && ! $promotedInfo),
            'is_top_root' => $isTopRoot,
            'is_idle_slot' => $isIdleSlot,
            'idle_slot_label' => $idleLabel,
            'company_label' => $companyLabel,
            'appreciation_member' => $appreciationMember,
            'promoted_info' => $promotedInfo,
            'is_promoted_acting' => (bool) ($isIdleSlot && $promotedInfo && ! ($promotedInfo['is_expired'] ?? false)),
            'is_promoted_expired' => (bool) ($isIdleSlot && $promotedInfo && ($promotedInfo['is_expired'] ?? false)),
            'boosted_by_idle_slots' => $boostedLabels,
            'promotions_held' => $promotionsHeld,
            'is_dual_placed' => count($promotionsHeld) > 0,
            'downline_count' => $downlineCount,
            'status' => ($isIdleSlot && $promotedInfo) ? ($promotedInfo['status'] ?? 'active') : $member->status,
            'status_label' => ucfirst(($isIdleSlot && $promotedInfo) ? ($promotedInfo['status'] ?? 'active') : $member->status),
            'kyc_status' => ($isIdleSlot && $promotedInfo) ? ($promotedInfo['kyc_status'] ?? 'approved') : $member->kyc_status,
            'position' => $member->placement_position,
            'position_label' => $member->placement_position
                ? ucfirst($member->placement_position)
                : 'Root',
            'level' => $level,
            'level_label' => $level > 19 ? '20+' : (string) $level,
            'total_income' => (int) round($combinedIncome),
            'total_income_formatted' => '₹'.number_format(round($combinedIncome)),
            'slot_income_formatted' => '₹'.number_format(round($totalIncome)),
            'has_children' => $hasChildren,
            'sponsor' => $member->sponsor ? [
                'id' => $member->sponsor->id,
                'customer_id' => $member->sponsor->customer_id,
                'name' => $member->sponsor->user?->name ?? 'Unknown',
                'phone' => $member->sponsor->user?->phone ?? '',
            ] : null,
            'placement_parent' => $member->placementParent ? [
                'id' => $member->placementParent->id,
                'customer_id' => $member->placementParent->customer_id,
                'name' => $member->placementParent->user?->name ?? 'Unknown',
                'phone' => $member->placementParent->user?->phone ?? '',
            ] : null,
            'details_url' => admin_route('mlm.members.show', $member),
            'move_url' => admin_route('mlm.tree.move.form', $member),
        ];
    }

    public function allIdleSlots(): array
    {
        $idleCodes = [
            'C25070004' => 'Idle - 2',
            'C25070005' => 'Idle - 3',
            'C25070006' => 'Idle - 4',
            'C25070007' => 'Idle - 5',
            'C25070008' => 'Idle - 6',
            'C25070009' => 'Idle - 7',
            'C25070010' => 'Idle - 8',
            'C25070011' => 'Idle - 9',
            'C25070012' => 'Idle - 10',
            'C25070013' => 'Idle - 11',
            'C25070014' => 'Idle - 12',
            'C25070015' => 'Idle - 13',
        ];

        $slots = Member::query()
            ->with(['user', 'appreciationMember.user', 'placementParent.user'])
            ->where(function ($q) use ($idleCodes) {
                $q->where('is_idle_slot', true)
                    ->orWhereHas('user', fn ($uq) => $uq->whereIn('mlm_member_id', array_keys($idleCodes)));
            })
            ->get();

        return $slots->map(function (Member $slot) use ($idleCodes) {
            $cid = $slot->customer_id;
            $label = $slot->idle_slot_label ?? ($idleCodes[$cid] ?? "Idle Slot #{$slot->id}");
            $appMember = $slot->appreciationMember;
            $isOccupied = $slot->appreciation_member_id !== null;
            $isExpired = $slot->appreciation_expires_at && $slot->appreciation_expires_at->isPast();

            return [
                'id' => $slot->id,
                'customer_id' => $cid,
                'label' => $label,
                'position' => $slot->placement_position,
                'level' => $this->level($slot),
                'parent_name' => $slot->placementParent?->user?->name ?? 'Root',
                'is_occupied' => $isOccupied,
                'is_expired' => $isExpired,
                'promoted_member' => $appMember ? [
                    'id' => $appMember->id,
                    'customer_id' => $appMember->customer_id,
                    'name' => $appMember->user?->name,
                ] : null,
                'assigned_at' => $slot->appreciation_assigned_at?->format('d M Y'),
                'expires_at' => $slot->appreciation_expires_at?->format('d M Y'),
            ];
        })->values()->all();
    }

    public function searchPromotableMembers(string $term): array
    {
        $idleCodes = [
            'C25070003','C25070004','C25070005','C25070006',
            'C25070007','C25070008','C25070009','C25070010',
            'C25070011','C25070012','C25070013','C25070014','C25070015',
        ];
        $isPgsql = DB::connection()->getDriverName() === 'pgsql';
        $like = $isPgsql ? 'ilike' : 'like';
        $lowerTerm = strtolower($term);

        return Member::query()
            ->with(['user', 'placementParent.user', 'assignedAppreciationSlots'])
            ->where('is_idle_slot', false)
            ->whereHas('user', function ($q) use ($term, $idleCodes, $like, $lowerTerm) {
                $q->whereNotIn('mlm_member_id', $idleCodes)
                    ->where(function ($sub) use ($term, $like, $lowerTerm) {
                        $sub->where('mlm_member_id', $like, "%{$term}%")
                            ->orWhere('name', $like, "%{$term}%")
                            ->orWhere('phone', $like, "%{$term}%")
                            ->orWhereRaw('LOWER(name) LIKE ?', ["%{$lowerTerm}%"])
                            ->orWhereRaw('LOWER(mlm_member_id) LIKE ?', ["%{$lowerTerm}%"]);
                    });
            })
            ->limit(15)
            ->get()
            ->map(function (Member $m) {
                $level = $this->level($m);
                $activeSlots = $m->assignedAppreciationSlots->map(fn ($s) => $s->idle_slot_label ?? $s->customer_id)->all();

                return [
                    'id' => $m->id,
                    'customer_id' => $m->customer_id,
                    'name' => $m->user?->name ?? 'Distributor',
                    'phone' => $m->user?->phone ?? '',
                    'level' => $level,
                    'level_label' => (string) $level,
                    'downlines' => $this->getDownlineCount($m->id),
                    'active_promotions' => $activeSlots,
                ];
            })
            ->values()
            ->all();
    }

    public function search(string $term): array
    {
        $isPgsql = DB::connection()->getDriverName() === 'pgsql';
        $like = $isPgsql ? 'ilike' : 'like';
        $lowerTerm = strtolower($term);

        return Member::query()
            ->with(['user', 'sponsor.user', 'placementParent.user'])
            ->whereHas('user', function ($query) use ($term, $like, $lowerTerm): void {
                $query->where('mlm_member_id', $like, "%{$term}%")
                    ->orWhere('name', $like, "%{$term}%")
                    ->orWhere('phone', $like, "%{$term}%")
                    ->orWhere('email', $like, "%{$term}%")
                    ->orWhereRaw('LOWER(name) LIKE ?', ["%{$lowerTerm}%"])
                    ->orWhereRaw('LOWER(mlm_member_id) LIKE ?', ["%{$lowerTerm}%"]);
            })
            ->orderBy('id')
            ->limit(25)
            ->get()
            ->map(function (Member $member): ?array {
                $path = $this->placementPath($member);

                if ($path === null) {
                    return null;
                }

                $payload = $this->memberPayload($member);

                return array_merge($payload, [
                    'member' => $payload,
                    'root_id' => $path[0],
                    'path' => $path,
                ]);
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
