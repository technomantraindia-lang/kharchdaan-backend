<?php

namespace App\Services\Mlm;

use App\Models\Member;
use App\Models\PlacementMovement;
use App\Models\User;
use App\Services\ActivityLogService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
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

    public function searchParents(Member $movingMember, string $term): array
    {
        $descendantIds = $this->subtree($movingMember)->pluck('id')->all();

        return Member::query()
            ->with('user')
            ->where('status', Member::STATUS_ACTIVE)
            ->whereNotIn('id', $descendantIds)
            ->whereHas('user', function ($query) use ($term): void {
                $query->where('mlm_member_id', 'like', "%{$term}%")
                    ->orWhere('name', 'like', "%{$term}%")
                    ->orWhere('phone', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%");
            })
            ->orderBy('id')
            ->limit(25)
            ->get()
            ->map(fn (Member $parent): array => [
                'id' => $parent->id,
                'customer_id' => $parent->customer_id,
                'name' => $parent->user?->name ?? 'Unnamed member',
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

        $this->validateMove($member, $newParent, $data['new_position']);
        $subtree = $this->subtree($member);
        $oldPath = $this->placementPath($member) ?? [$member->id];
        $newPath = array_merge($this->placementPath($newParent) ?? [$newParent->id], [$member->id]);
        $oldDepth = count($oldPath) - 1;
        $newDepth = count($newPath) - 1;

        return [
            'member_id' => $member->id,
            'member_customer_id' => $member->customer_id,
            'member_name' => $member->user?->name,
            'old_parent' => $this->memberReference($member->placementParent),
            'new_parent' => $this->memberReference($newParent),
            'old_position' => $member->placement_position,
            'new_position' => $data['new_position'],
            'affected_subtree_count' => $subtree->count(),
            'old_depth' => $oldDepth,
            'new_depth' => $newDepth,
            'old_path' => $this->pathPayload($oldPath),
            'new_path' => $this->pathPayload($newPath),
            'effective_at' => Carbon::parse($data['effective_at'])->toDateTimeString(),
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

            $this->validateMove($lockedMember, $newParent, $data['new_position']);

            $oldParentId = $lockedMember->placement_parent_id;
            $oldPosition = $lockedMember->placement_position;
            $oldPath = $this->placementPath($lockedMember) ?? [$lockedMember->id];
            $newPath = array_merge($this->placementPath($newParent) ?? [$newParent->id], [$lockedMember->id]);
            $subtreeCount = $this->subtree($lockedMember)->count();

            $lockedMember->update([
                'placement_parent_id' => $newParent->id,
                'placement_position' => $data['new_position'],
            ]);

            $movement = PlacementMovement::create([
                'member_id' => $lockedMember->id,
                'old_parent_id' => $oldParentId,
                'new_parent_id' => $newParent->id,
                'old_position' => $oldPosition,
                'new_position' => $data['new_position'],
                'reason' => trim($data['reason']),
                'moved_by' => $admin->id,
                'effective_at' => Carbon::parse($data['effective_at']),
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
                "Moved member {$lockedMember->customer_id} to {$newParent->customer_id} / {$data['new_position']}",
                $lockedMember,
                ['placement_parent_id' => $oldParentId, 'placement_position' => $oldPosition],
                ['placement_parent_id' => $newParent->id, 'placement_position' => $data['new_position'], 'affected_subtree_count' => $subtreeCount]
            );

            return $movement->fresh(['member.user', 'oldParent.user', 'newParent.user', 'movedBy']);
        });
    }

    private function validateMove(Member $member, Member $newParent, string $newPosition): void
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

        $descendantIds = $this->subtree($member)->pluck('id')->all();
        if (in_array($newParent->id, $descendantIds, true)) {
            $this->fail('new_parent_id', 'A member cannot be placed under their own descendant.');
        }

        $occupied = Member::query()
            ->where('placement_parent_id', $newParent->id)
            ->where('placement_position', $newPosition)
            ->whereKeyNot($member->id)
            ->exists();
        if ($occupied) {
            $this->fail('new_position', 'The selected placement position is already occupied.');
        }

        $newParentPath = $this->placementPath($newParent);
        if ($newParentPath === null) {
            $this->fail('new_parent_id', 'The new placement parent has an invalid circular path.');
        }

        $newDepth = count($newParentPath);
        $subtreeDepth = $this->subtreeDepth($member);
        if ($newDepth + $subtreeDepth > self::MAX_LEVEL) {
            $this->fail('new_parent_id', 'This move would place part of the subtree beyond Level 19.');
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

    private function placementPath(Member $member): ?array
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

            $current = Member::query()->find($current->placement_parent_id);
        }

        return null;
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
