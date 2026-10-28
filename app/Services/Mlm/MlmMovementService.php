<?php

namespace App\Services\Mlm;

use App\Models\Member;
use App\Models\PlacementMovement;
use App\Models\Role;
use App\Models\User;
use App\Services\ActivityLogService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class MlmMovementService
{
    public const MAX_LEVEL = 19;

    public function context(Member $member): array
    {
        $member->load(['user', 'placementParent.user', 'sponsor.user']);
        $subtree = $this->subtree($member);
        $currentPath = $this->placementPath($member);

        return [
            'member' => $member,
            'current_parent' => $member->placementParent,
            'current_position' => $member->placement_position,
            'subtree_count' => $subtree->count(),
            'current_depth' => $currentPath ? count($currentPath) - 1 : 0,
            'current_path' => $this->pathPayload($currentPath ?? [$member->id]),
        ];
    }

    public function searchParents(Member $movingMember, string $term, bool $isSubtreeMove = false): array
    {
        $descendantIds = $isSubtreeMove ? $this->subtree($movingMember)->pluck('id')->all() : [$movingMember->id];
        $isPgsql = DB::connection()->getDriverName() === 'pgsql';
        $like = $isPgsql ? 'ilike' : 'like';
        $lowerTerm = strtolower($term);

        $candidates = Member::query()
            ->with(['user', 'placementParent.user'])
            ->where('status', Member::STATUS_ACTIVE)
            ->whereNotIn('id', $descendantIds)
            ->where(function ($q) use ($term, $like, $lowerTerm): void {
                $q->whereHas('user', function ($query) use ($term, $like, $lowerTerm): void {
                    $query->where('mlm_member_id', $like, "%{$term}%")
                        ->orWhere('name', $like, "%{$term}%")
                        ->orWhere('phone', $like, "%{$term}%")
                        ->orWhere('email', $like, "%{$term}%")
                        ->orWhereRaw('LOWER(name) LIKE ?', ["%{$lowerTerm}%"])
                        ->orWhereRaw('LOWER(mlm_member_id) LIKE ?', ["%{$lowerTerm}%"]);
                })
                ->orWhere('idle_slot_label', $like, "%{$term}%");
            })
            ->orderBy('id')
            ->limit(30)
            ->get();

        // Also, if the search term matches an idle slot that is currently a child of another member,
        // include that child's parent as a candidate parent
        $matchingIdleParents = Member::query()
            ->with(['placementParent.user'])
            ->where('is_idle_slot', true)
            ->whereNotNull('placement_parent_id')
            ->where(function ($q) use ($term, $like, $lowerTerm): void {
                $q->where('idle_slot_label', $like, "%{$term}%")
                    ->orWhereHas('user', function ($uq) use ($term, $like, $lowerTerm): void {
                        $uq->where('mlm_member_id', $like, "%{$term}%")
                            ->orWhere('name', $like, "%{$term}%");
                    });
            })
            ->get()
            ->pluck('placementParent')
            ->filter(fn ($p) => $p && ! in_array($p->id, $descendantIds, true));

        $allCandidates = $candidates->merge($matchingIdleParents)->unique('id');

        return $allCandidates
            ->map(function (Member $parent) use ($movingMember): array {
                $available = $this->availablePositions($movingMember, $parent);

                // Slot details (showing which slots are open or vacant idle slots)
                $slotDetails = [];
                $children = Member::query()
                    ->with('user')
                    ->where('placement_parent_id', $parent->id)
                    ->get()
                    ->keyBy('placement_position');

                foreach ($available as $slotPos) {
                    $child = $children->get($slotPos);
                    $slotDetails[] = [
                        'position' => $slotPos,
                        'is_idle' => $child?->is_idle_slot ?? false,
                        'label' => ucfirst($slotPos) . ' Slot' . ($child?->is_idle_slot ? ' [Vacant ' . ($child->user?->name ?? 'Idle') . ']' : ' [Open]'),
                    ];
                }

                return [
                    'id' => $parent->id,
                    'customer_id' => $parent->customer_id,
                    'name' => $parent->user?->name ?? 'Unnamed member',
                    'phone' => $parent->user?->phone ?? '',
                    'status' => $parent->status,
                    'is_idle' => (bool) $parent->is_idle_slot,
                    'available_positions' => $available,
                    'slot_details' => $slotDetails,
                ];
            })
            ->filter(fn (array $parent): bool => $parent['available_positions'] !== [])
            ->values()
            ->all();
    }

    public function availablePositions(Member $movingMember, Member $newParent): array
    {
        $occupied = Member::query()
            ->where('placement_parent_id', $newParent->id)
            ->whereKeyNot($movingMember->id)
            ->where('is_idle_slot', false)
            ->where('status', Member::STATUS_ACTIVE)
            ->pluck('placement_position')
            ->all();

        return array_values(array_diff(Member::POSITIONS, $occupied));
    }

    public function preview(Member $member, array $data): array
    {
        $newParent = Member::query()
            ->with(['user', 'placementParent.user'])
            ->find($data['new_parent_id']);

        if (! $newParent) {
            $this->fail('new_parent_id', 'The selected placement parent is invalid.');
        }

        $isSubtreeMove = ($data['move_mode'] ?? 'single') === 'subtree'
            || ($data['with_downlines'] ?? false) == '1';

        $this->validateMove($member, $newParent, $data['new_position'], $isSubtreeMove);

        $oldPath = $this->placementPath($member) ?? [$member->id];
        $newPath = array_merge($this->placementPath($newParent) ?? [$newParent->id], [$member->id]);
        $oldDepth = count($oldPath) - 1;
        $newDepth = count($newPath) - 1;

        $affectedCount = $isSubtreeMove ? $this->subtree($member)->count() : 1;

        return [
            'member_id' => $member->id,
            'member_customer_id' => $member->customer_id,
            'member_name' => $member->user?->name,
            'old_parent' => $this->memberReference($member->placementParent),
            'new_parent' => $this->memberReference($newParent),
            'old_position' => $member->placement_position,
            'new_position' => $data['new_position'],
            'affected_subtree_count' => $affectedCount,
            'move_mode' => $isSubtreeMove ? 'subtree' : 'single',
            'keeps_blank_cell' => ! $isSubtreeMove,
            'old_depth' => $oldDepth,
            'new_depth' => $newDepth,
            'old_path' => $this->pathPayload($oldPath),
            'new_path' => $this->pathPayload($newPath),
            'effective_at' => Carbon::parse($data['effective_at'] ?? now())->toDateTimeString(),
        ];
    }

    public function move(Member $member, array $data, User $admin): PlacementMovement
    {
        return DB::transaction(function () use ($member, $data, $admin): PlacementMovement {
            $lockedMember = Member::query()->lockForUpdate()->findOrFail($member->id);
            $newParent = Member::query()->lockForUpdate()->find($data['new_parent_id']);

            if (! $newParent) {
                $this->fail('new_parent_id', 'The selected placement parent is invalid.');
            }

            $isSubtreeMove = ($data['move_mode'] ?? 'single') === 'subtree'
                || ($data['with_downlines'] ?? false) == '1';

            $this->validateMove($lockedMember, $newParent, $data['new_position'], $isSubtreeMove);

            $oldParentId = $lockedMember->placement_parent_id;
            $oldPosition = $lockedMember->placement_position;
            $oldPath = $this->placementPath($lockedMember) ?? [$lockedMember->id];
            $newPath = array_merge($this->placementPath($newParent) ?? [$newParent->id], [$lockedMember->id]);

            $existingChildren = Member::query()->where('placement_parent_id', $lockedMember->id)->get();

            // Find any idle slot currently occupying the target position
            $targetIdleSlot = Member::query()
                ->where('placement_parent_id', $newParent->id)
                ->where('placement_position', $data['new_position'])
                ->whereKeyNot($lockedMember->id)
                ->where('is_idle_slot', true)
                ->first();

            if (! $isSubtreeMove) {
                // Client Requirement:
                // Move only selected member ID.
                // Downline members do NOT follow the member into the new parent.
                // Keep blank select cell for future use in previous position.
                // Existing downlines stay attached under this blank select cell!

                // 1. Temporarily vacate lockedMember from old position to satisfy unique (placement_parent_id, placement_position) constraint
                $lockedMember->update([
                    'placement_parent_id' => null,
                    'placement_position' => null,
                ]);

                // 2. Create or assign blank select cell in the vacated slot for future use
                $blankSlot = null;
                if ($oldParentId && $oldPosition) {
                    $blankSlot = $this->createOrAssignBlankSlot($oldParentId, $oldPosition, $lockedMember, $admin);
                }

                // 3. Re-parent existing children to the blank slot so downlines stay in place!
                if ($existingChildren->isNotEmpty()) {
                    foreach ($existingChildren as $child) {
                        $updatePayload = [
                            'placement_parent_id' => $blankSlot ? $blankSlot->id : null,
                        ];
                        if (Schema::hasColumn('mlm_members', 'detached_parent_id')) {
                            $updatePayload['detached_parent_id'] = $oldParentId;
                            $updatePayload['detached_parent_position'] = $oldPosition;
                            $updatePayload['detached_member_id'] = $blankSlot ? $blankSlot->id : $lockedMember->id;
                            $updatePayload['detached_child_position'] = $child->placement_position;
                        }
                        $child->update($updatePayload);
                    }
                }

                // 4. If target position was occupied by an idle slot with waiting children,
                // re-parent those children under the newly arriving member
                if ($targetIdleSlot) {
                    Member::query()
                        ->where('placement_parent_id', $targetIdleSlot->id)
                        ->update(['placement_parent_id' => $lockedMember->id]);

                    $targetIdleSlot->update([
                        'placement_parent_id' => null,
                        'placement_position' => null,
                        'status' => Member::STATUS_INACTIVE,
                    ]);
                }

                // 5. Move lockedMember to target parent and position
                $lockedMember->update([
                    'placement_parent_id' => $newParent->id,
                    'placement_position' => $data['new_position'],
                ]);

                // Check if target position or returning member has waiting downlines
                $this->reconnectWaitingChildren($lockedMember, $newParent->id, $data['new_position']);

                $subtreeCount = 1;
            } else {
                // Move with complete subtree
                if ($targetIdleSlot) {
                    $targetIdleSlot->update([
                        'placement_parent_id' => null,
                        'placement_position' => null,
                        'status' => Member::STATUS_INACTIVE,
                    ]);
                }

                $lockedMember->update([
                    'placement_parent_id' => $newParent->id,
                    'placement_position' => $data['new_position'],
                ]);

                // Check if target position or returning member has waiting downlines
                $this->reconnectWaitingChildren($lockedMember, $newParent->id, $data['new_position']);

                $subtreeCount = $this->subtree($lockedMember)->count();
            }

            $movement = PlacementMovement::create([
                'member_id' => $lockedMember->id,
                'old_parent_id' => $oldParentId,
                'new_parent_id' => $newParent->id,
                'old_position' => $oldPosition,
                'new_position' => $data['new_position'],
                'reason' => trim($data['reason'] ?? 'Administrative placement move'),
                'moved_by' => $admin->id,
                'effective_at' => Carbon::parse($data['effective_at'] ?? now()),
                'affected_subtree_count' => $subtreeCount,
                'status' => 'approved',
                'approved_at' => now(),
                'old_path_snapshot' => $this->pathPayload($oldPath),
                'new_path_snapshot' => $this->pathPayload($newPath),
                'old_depth' => count($oldPath) - 1,
                'new_depth' => count($newPath) - 1,
            ]);

            ActivityLogService::log(
                'placement_move',
                'mlm_members',
                "Moved member {$lockedMember->customer_id} to {$newParent->customer_id} / {$data['new_position']} (Scope: " . ($isSubtreeMove ? 'Subtree' : 'Single Member with blank cell preserved') . ")",
                $lockedMember,
                ['placement_parent_id' => $oldParentId, 'placement_position' => $oldPosition],
                ['placement_parent_id' => $newParent->id, 'placement_position' => $data['new_position'], 'affected_subtree_count' => $subtreeCount]
            );

            return $movement->fresh(['member.user', 'oldParent.user', 'newParent.user', 'movedBy']);
        });
    }

    public function reconnectWaitingChildren(Member $occupant, ?int $parentId, ?string $position): void
    {
        if (Schema::hasColumn('mlm_members', 'detached_parent_id') && $parentId && $position) {
            $waiting = Member::query()
                ->where('detached_parent_id', $parentId)
                ->where('detached_parent_position', $position)
                ->where('id', '!=', $occupant->id)
                ->get();

            foreach ($waiting as $child) {
                $childPos = $child->detached_child_position ?: $child->placement_position;
                if ($childPos && ! Member::query()->where('placement_parent_id', $occupant->id)->where('placement_position', $childPos)->exists()) {
                    $child->update([
                        'placement_parent_id' => $occupant->id,
                        'placement_position' => $childPos,
                        'status' => Member::STATUS_ACTIVE,
                        'detached_parent_id' => null,
                        'detached_parent_position' => null,
                        'detached_member_id' => null,
                        'detached_child_position' => null,
                    ]);
                }
            }
        }

        // Auto-reconnect master downlines via tree service
        app(MlmTreeService::class)->autoReconnectOrphanedDownlines($occupant);
    }

    public function createOrAssignBlankSlot(?int $oldParentId, ?string $oldPosition, Member $movingMember, User $admin): ?Member
    {
        if (! $oldParentId || ! $oldPosition) {
            return null;
        }

        $existingBlank = Member::query()
            ->where('placement_parent_id', $oldParentId)
            ->where('placement_position', $oldPosition)
            ->where('is_idle_slot', true)
            ->whereKeyNot($movingMember->id)
            ->first();

        if ($existingBlank) {
            $existingBlank->update(['status' => Member::STATUS_ACTIVE]);
            return $existingBlank;
        }

        // Generate sequential unique vacant slot code and label
        $count = Member::query()->where('is_idle_slot', true)->count() + 1;
        $uniqueCode = 'C2507' . str_pad((string) $count, 4, '0', STR_PAD_LEFT);
        while (User::where('mlm_member_id', $uniqueCode)->orWhere('email', strtolower($uniqueCode) . '@kharchdaan.com')->exists()) {
            $count++;
            $uniqueCode = 'C2507' . str_pad((string) $count, 4, '0', STR_PAD_LEFT);
        }

        $customerRole = Role::firstOrCreate(['name' => 'Customer', 'guard_name' => 'web']);
        $slotLabel = 'Idle - ' . $count;

        $user = User::create([
            'name' => $slotLabel,
            'email' => strtolower($uniqueCode) . '@kharchdaan.com',
            'password' => Hash::make('password'),
            'plain_password' => 'password',
            'phone' => null,
            'role_id' => $customerRole->id,
            'status' => 'active',
            'mlm_member_id' => $uniqueCode,
        ]);

        return Member::create([
            'user_id' => $user->id,
            'sponsor_member_id' => $movingMember->sponsor_member_id,
            'placement_parent_id' => $oldParentId,
            'placement_position' => $oldPosition,
            'status' => Member::STATUS_ACTIVE,
            'is_idle_slot' => true,
            'idle_slot_label' => $slotLabel,
            'joined_at' => now(),
            'created_by' => $admin->id,
            'kyc_status' => Member::KYC_APPROVED,
        ]);
    }

    private function validateMove(Member $member, Member $newParent, string $newPosition, bool $isSubtreeMove = false): void
    {
        if ($this->placementPath($member) === null) {
            $this->fail('member', 'The selected member has an invalid circular placement path.');
        }

        if ($member->id === $newParent->id) {
            $this->fail('new_parent_id', 'A member cannot be placed under themselves.');
        }

        if ($newParent->status !== Member::STATUS_ACTIVE) {
            $this->fail('new_parent_id', 'The new placement parent must be active.');
        }

        if (! in_array($newPosition, Member::POSITIONS, true)) {
            $this->fail('new_position', 'Select a valid placement position.');
        }

        // Slot occupancy check (can replace an unassigned vacant idle slot if needed)
        $occupied = Member::query()
            ->where('placement_parent_id', $newParent->id)
            ->where('placement_position', $newPosition)
            ->whereKeyNot($member->id)
            ->where('status', Member::STATUS_ACTIVE)
            ->first();

        if ($occupied && ! $occupied->is_idle_slot) {
            $this->fail('new_position', 'The selected placement position is already occupied by ' . ($occupied->user?->name ?? 'another distributor') . '.');
        }

        $newParentPath = $this->placementPath($newParent);
        if ($newParentPath === null) {
            $this->fail('new_parent_id', 'The new placement parent has an invalid circular path.');
        }

        $newDepth = count($newParentPath);

        $descendantIds = $this->subtree($member)->pluck('id')->all();
        if (in_array($newParent->id, $descendantIds, true)) {
            $this->fail('new_parent_id', 'A member cannot be placed under their own descendant.');
        }

        if ($isSubtreeMove) {
            $subtreeDepth = $this->subtreeDepth($member);
            if ($newDepth + $subtreeDepth > self::MAX_LEVEL) {
                $this->fail('new_parent_id', 'This move would place part of the subtree beyond Level 19.');
            }
        } else {
            if ($newDepth > self::MAX_LEVEL) {
                $this->fail('new_parent_id', 'This move would place the member beyond Level 19.');
            }
        }
    }

    private function subtree(Member $member): Collection
    {
        $members = collect([$member]);
        $queue = collect([$member->id]);
        $visited = [$member->id => true];

        while ($queue->isNotEmpty()) {
            $children = Member::query()
                ->whereIn('placement_parent_id', $queue->all())
                ->whereNotIn('id', array_keys($visited))
                ->get();
            if ($children->isEmpty()) {
                break;
            }

            $members = $members->merge($children);
            foreach ($children as $child) {
                $visited[$child->id] = true;
            }
            $queue = $children->pluck('id');
        }

        return $members->unique('id')->values();
    }

    private function subtreeDepth(Member $member): int
    {
        $depth = 0;
        $frontier = collect([$member->id]);
        $visited = [$member->id => true];

        while ($frontier->isNotEmpty()) {
            $children = Member::query()
                ->whereIn('placement_parent_id', $frontier->all())
                ->whereNotIn('id', array_keys($visited))
                ->get();
            if ($children->isEmpty()) {
                break;
            }

            $depth++;
            foreach ($children as $child) {
                $visited[$child->id] = true;
            }
            $frontier = $children->pluck('id');
            if ($depth > self::MAX_LEVEL) {
                break;
            }
        }

        return $depth;
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

            if (count($path) > self::MAX_LEVEL + 1) {
                return $path;
            }

            $parent = Member::query()->find($current->placement_parent_id);
            if (! $parent) {
                return $path;
            }

            $current = $parent;
        }

        return ! empty($path) ? $path : null;
    }

    private function pathPayload(?array $path): array
    {
        if (! $path) {
            return [];
        }

        return Member::query()
            ->with('user')
            ->whereIn('id', $path)
            ->get()
            ->sortBy(fn (Member $member): int => array_search($member->id, $path, true))
            ->map(fn (Member $member): array => [
                'id' => $member->id,
                'customer_id' => $member->customer_id,
                'name' => $member->user?->name,
            ])
            ->values()
            ->all();
    }

    private function memberReference(?Member $member): ?array
    {
        return $member ? [
            'id' => $member->id,
            'customer_id' => $member->customer_id,
            'name' => $member->user?->name,
        ] : null;
    }

    private function fail(string $field, string $message): never
    {
        throw ValidationException::withMessages([$field => $message]);
    }
}
