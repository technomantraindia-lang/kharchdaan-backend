<?php

namespace App\Services\Mlm;

use App\Models\Member;
use App\Models\PlacementMovement;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class SponsorPlacementService
{
    public function __construct(
        private readonly MlmCustomerNetworkService $network,
        private readonly PurchaseEligibilityService $eligibility,
        private readonly MlmMovementService $movements,
    ) {}

    public function page(User $sponsorUser): array
    {
        $sponsor = $this->network->ownedMember($sponsorUser);
        $placeable = $this->placeableJoinees($sponsor);
        $openSlots = $this->openSlotsUnderSponsor($sponsor);

        return [
            'sponsor' => [
                'id' => $sponsor->id,
                'customer_id' => $sponsor->customer_id,
                'name' => $sponsor->user?->name,
            ],
            'placeable_joinees' => $placeable,
            'open_slots' => $openSlots,
            'min_purchase' => PurchaseEligibilityService::REFERRAL_MIN_TRANSACTION,
            'note' => 'You can place a sponsored joinee into an empty slot under your placement network only after they have a qualifying purchase of ₹'
                .PurchaseEligibilityService::REFERRAL_MIN_TRANSACTION.' or more.',
        ];
    }

    public function place(User $sponsorUser, array $data): PlacementMovement
    {
        $sponsor = $this->network->ownedMember($sponsorUser);

        $joinee = Member::query()
            ->realDistributors()
            ->with('user')
            ->whereKey($data['joinee_member_id'])
            ->where('sponsor_member_id', $sponsor->id)
            ->first();

        if (! $joinee) {
            throw ValidationException::withMessages([
                'joinee_member_id' => 'Select a joinee you personally sponsored.',
            ]);
        }

        if (! $this->eligibility->memberHasMinPurchase($joinee, PurchaseEligibilityService::REFERRAL_MIN_TRANSACTION)) {
            throw ValidationException::withMessages([
                'joinee_member_id' => 'Joinee must complete at least ₹'
                    .PurchaseEligibilityService::REFERRAL_MIN_TRANSACTION.' in qualifying purchases before manual placement.',
            ]);
        }

        $newParent = Member::query()->find($data['new_parent_id']);
        if (! $newParent || ! $this->isInPlacementSubtree($sponsor, $newParent)) {
            throw ValidationException::withMessages([
                'new_parent_id' => 'Target parent must be inside your placement network (including yourself).',
            ]);
        }

        return $this->movements->move($joinee, [
            'new_parent_id' => $newParent->id,
            'new_position' => $data['new_position'],
            'move_mode' => 'single',
            'reason' => trim((string) ($data['reason'] ?? 'Sponsor manual placement after ₹200 purchase')),
            'effective_at' => now()->toDateTimeString(),
        ], $sponsorUser);
    }

    private function placeableJoinees(Member $sponsor): array
    {
        return Member::query()
            ->realDistributors()
            ->with('user')
            ->where('sponsor_member_id', $sponsor->id)
            ->orderByDesc('id')
            ->get()
            ->map(function (Member $joinee) {
                $eligible = $this->eligibility->memberHasMinPurchase(
                    $joinee,
                    PurchaseEligibilityService::REFERRAL_MIN_TRANSACTION
                );

                return [
                    'id' => $joinee->id,
                    'customer_id' => $joinee->customer_id,
                    'name' => $joinee->user?->name,
                    'phone' => $joinee->user?->phone,
                    'placement_parent_id' => $joinee->placement_parent_id,
                    'placement_position' => $joinee->placement_position,
                    'purchase_eligible' => $eligible,
                    'can_place' => $eligible,
                ];
            })
            ->values()
            ->all();
    }

    private function openSlotsUnderSponsor(Member $sponsor): array
    {
        $nodes = collect([$sponsor])->merge($this->placementDescendants($sponsor));

        $slots = [];
        foreach ($nodes as $parent) {
            $occupied = Member::query()
                ->where('placement_parent_id', $parent->id)
                ->whereIn('placement_position', Member::POSITIONS)
                ->get()
                ->keyBy('placement_position');

            foreach (Member::POSITIONS as $position) {
                $occupant = $occupied->get($position);
                $isOpen = ! $occupant || $occupant->isVacantEmptySlot();
                if (! $isOpen) {
                    continue;
                }

                $slots[] = [
                    'parent_id' => $parent->id,
                    'parent_customer_id' => $parent->customer_id,
                    'parent_name' => $parent->user?->name ?? $parent->idle_slot_label ?? 'Member',
                    'position' => $position,
                    'position_label' => ucfirst($position),
                    'label' => ($parent->customer_id ?? 'ID '.$parent->id).' / '.ucfirst($position),
                ];
            }
        }

        return $slots;
    }

    private function placementDescendants(Member $root): Collection
    {
        $frontier = collect([$root->id]);
        $members = collect();

        while ($frontier->isNotEmpty()) {
            $children = Member::query()
                ->with('user')
                ->whereIn('placement_parent_id', $frontier->all())
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

    private function isInPlacementSubtree(Member $root, Member $candidate): bool
    {
        if ($candidate->id === $root->id) {
            return true;
        }

        $currentId = $candidate->id;
        $visited = [];

        while ($currentId !== null && ! isset($visited[$currentId])) {
            if ($currentId === $root->id) {
                return true;
            }
            $visited[$currentId] = true;
            $currentId = Member::query()->whereKey($currentId)->value('placement_parent_id');
        }

        return false;
    }
}
