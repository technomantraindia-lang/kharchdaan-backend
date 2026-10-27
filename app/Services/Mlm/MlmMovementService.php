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

        return Member::query()
            ->with('user')
            ->where('status', Member::STATUS_ACTIVE)
            ->whereNotIn('id', $descendantIds)
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
            ->map(fn (Member $parent): array => [
                'id' => $parent->id,
                'customer_id' => $parent->customer_id,
                'name' => $parent->user?->name ?? 'Unnamed member',
                'phone' => $parent->user?->phone ?? '',
                'status' => $parent->status,
                'available_positions' => $this->availablePositions($movingMember, $parent),
            ])
            ->filter(fn (array $parent): bool => $parent['available_positions'] !== [])
            ->values()
            ->all();
    }

    public function availablePositions(Member $movingMember, Member $newParent): array
    {
        $occupied = Member::query()
            ->where('placement_parent_id', $newParent->id)
            ->whereKeyNot($movingMember->id)
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

            // Clear any idle slot occupying the target position to prevent duplicate positioning
            Member::query()
                ->where('placement_parent_id', $newParent->id)
                ->where('placement_position', $data['new_position'])
                ->whereKeyNot($lockedMember->id)
                ->where('is_idle_slot', true)
                ->update([
                    'placement_parent_id' => null,
                    'placement_position' => null,
                ]);

            if (! $isSubtreeMove) {
                // Client Requirement:
                // Move only selected member ID.
                // Downline members do NOT follow the member into the new parent.
                // Retain detachment information so when moving back or when a member occupies this slot, they are restored.
                if ($existingChildren->isNotEmpty()) {
                    foreach ($existingChildren as $child) {
                        $updatePayload = [
                            'placement_parent_id' => null,
                            'placement_position' => null,
                        ];
                        if (Schema::hasColumn('mlm_members', 'detached_parent_id')) {
                            $updatePayload['detached_parent_id'] = $oldParentId;
                            $updatePayload['detached_parent_position'] = $oldPosition;
                            $updatePayload['detached_member_id'] = $lockedMember->id;
                            $updatePayload['detached_child_position'] = $child->placement_position;
                        }
                        $child->update($updatePayload);
                    }
                }

                // Move lockedMember to target parent and position.
                // Old position is now an empty Available Slot.
                $lockedMember->update([
                    'placement_parent_id' => $newParent->id,
                    'placement_position' => $data['new_position'],
                ]);

                // Check if target position or returning member has waiting downlines
                $this->reconnectWaitingChildren($lockedMember, $newParent->id, $data['new_position']);

                $subtreeCount = 1;
            } else {
                // Move with complete subtree
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

    public function createOrAssignBlankSlot(?int $oldParentId, ?string $oldPosition, Member $movingMember, User $admin): Member
    {
        if ($oldParentId) {
            $existingBlank = Member::query()
                ->where('placement_parent_id', $oldParentId)
                ->where('placement_position', $oldPosition)
                ->where('is_idle_slot', true)
                ->whereKeyNot($movingMember->id)
                ->first();

            if ($existingBlank) {
                return $existingBlank;
            }
        }

        // Generate a clean vacant slot code
        $seq = Member::query()->where('is_idle_slot', true)->count() + 1;
        $uniqueCode = 'VACANT_' . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
        while (User::where('mlm_member_id', $uniqueCode)->exists()) {
            $seq++;
            $uniqueCode = 'VACANT_' . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
        }

        $customerRoleId = Role::where('name', 'Customer')->value('id') ?? 4;

        $user = User::create([
            'name' => 'Vacant Slot',
            'email' => strtolower($uniqueCode) . '@kharchdaan.com',
            'password' => Hash::make('password'),
            'plain_password' => 'password',
            'phone' => '',
            'role_id' => $customerRoleId,
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
            'idle_slot_label' => 'Vacant Slot',
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
