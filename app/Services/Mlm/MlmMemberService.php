<?php

namespace App\Services\Mlm;

use App\Models\Member;
use App\Models\Role;
use App\Models\User;
use App\Services\ActivityLogService;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class MlmMemberService
{
    public function __construct(
        private readonly MlmMemberIdGenerator $idGenerator,
        private readonly MlmSponsorService $sponsorService,
        private readonly MlmKycService $kycService,
    ) {}

    public function create(
        array $data,
        User $admin,
        ?UploadedFile $profilePhoto = null,
        ?UploadedFile $cancelledCheque = null
    ): Member {
        return DB::transaction(function () use ($data, $admin, $profilePhoto, $cancelledCheque): Member {
            $sponsor = $this->sponsorService->resolve(
                $data['sponsor_customer_id'] ?? null,
                null,
                (bool) ($data['root_member'] ?? false)
            );
            $user = $this->resolveCustomer($data['user_id'] ?? null);
            $customerRole = Role::where('name', 'Customer')->first();

            if (! $customerRole) {
                throw ValidationException::withMessages([
                    'user_id' => 'The existing Customer role is not configured.',
                ]);
            }

            $joiningDate = ! empty($data['joining_date'])
                ? Carbon::parse($data['joining_date'])
                : now();
            $memberId = $this->idGenerator->next($joiningDate);

            $user ??= new User([
                'password' => Hash::make(Str::random(40)),
                'role_id' => $customerRole->id,
                'status' => 'active',
            ]);
            $user->fill([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => trim($data['mobile']),
                'role_id' => $customerRole->id,
                'mlm_member_id' => $memberId,
            ]);
            $user->save();

            $member = Member::create([
                'user_id' => $user->id,
                'sponsor_member_id' => $sponsor?->id,
                'status' => $data['status'],
                'joined_at' => $joiningDate,
                'created_by' => $admin->id,
                'sponsor_name_snapshot' => $sponsor?->user?->name,
                'sponsor_relationship_status' => $sponsor ? 'active' : null,
                'sponsor_assigned_at' => $sponsor ? now() : null,
                'kyc_status' => $data['kyc_status'] ?? Member::KYC_PENDING,
            ]);

            $this->storeProfilePhoto($member, $profilePhoto);
            $this->kycService->save($member, $data, $cancelledCheque, $admin);

            ActivityLogService::log(
                'create',
                'mlm_members',
                "Created MLM member {$member->customer_id} for {$user->name}",
                $member,
                null,
                $this->snapshot($member->load('user'))
            );

            return $member->fresh(['user', 'sponsor.user']);
        });
    }

    public function update(
        Member $member,
        array $data,
        User $admin,
        ?UploadedFile $profilePhoto = null,
        ?UploadedFile $cancelledCheque = null
    ): Member {
        return DB::transaction(function () use ($member, $data, $admin, $profilePhoto, $cancelledCheque): Member {
            $member->load(['user', 'sponsor.user']);
            $oldValues = $this->snapshot($member);
            $mobileChanged = trim($member->user->phone ?? '') !== trim($data['mobile']);

            $member->user->fill([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => trim($data['mobile']),
            ]);
            $member->user->save();

            $member->fill([
                'status' => $data['status'],
            ]);
            $member->save();

            $this->storeProfilePhoto($member, $profilePhoto);
            $this->kycService->save($member, $data, $cancelledCheque, $admin);

            $description = "Updated MLM member {$member->customer_id}";
            if ($mobileChanged && ! empty($data['mobile_change_reason'])) {
                $description .= '. Mobile change reason: '.$data['mobile_change_reason'];
            }

            ActivityLogService::log(
                'update',
                'mlm_members',
                $description,
                $member,
                $oldValues,
                $this->snapshot($member->fresh(['user', 'sponsor.user']))
            );

            return $member->fresh(['user', 'sponsor.user']);
        });
    }

    public function setStatus(Member $member, string $status, User $admin): Member
    {
        return DB::transaction(function () use ($member, $status): Member {
            $oldStatus = $member->status;
            $member->update(['status' => $status]);

            ActivityLogService::log(
                'status_change',
                'mlm_members',
                "Changed member {$member->customer_id} status from {$oldStatus} to {$status}",
                $member,
                ['status' => $oldStatus],
                ['status' => $status]
            );

            return $member->fresh(['user', 'sponsor.user']);
        });
    }

    public function delete(Member $member, User $admin, bool $deleteUser = false): array
    {
        return DB::transaction(function () use ($member, $admin, $deleteUser): array {
            $memberId = $member->id;
            $member->loadMissing(['user', 'sponsor.user', 'placementParent.user']);
            $user = $member->user;
            $customerCode = $member->customer_id ?? "ID#{$memberId}";
            $memberName = $user?->name ?? 'Unknown Member';
            $isRootLeader = ($member->placement_parent_id === null);

            // 1. RE-ANCHOR DIRECT SPONSOR RELATIONSHIPS
            // Any members directly sponsored by this member inherit this member's sponsor
            $newSponsorId = $member->sponsor_member_id;
            $newSponsor = $newSponsorId ? Member::with('user')->find($newSponsorId) : null;
            $newSponsorName = $newSponsor?->user?->name ?? ($isRootLeader ? 'Direct Platform Root' : null);

            DB::table('mlm_members')->where('sponsor_member_id', $memberId)->update([
                'sponsor_member_id' => $newSponsorId,
                'sponsor_name_snapshot' => $newSponsorName,
                'sponsor_relationship_status' => $newSponsorId ? 'active' : null,
                'sponsor_assigned_at' => now(),
            ]);

            // 2. RE-ANCHOR 1:3 PLACEMENT MATRIX CHILDREN
            $directPlacementChildren = Member::where('placement_parent_id', $memberId)
                ->orderBy('placement_position')
                ->get();

            $promotedRootCode = null;

            if ($directPlacementChildren->isNotEmpty()) {
                if ($isRootLeader) {
                    // Deleted member was the ROOT LEADER!
                    // Promote the first placement child to become the NEW Matrix Root:
                    $newRoot = $directPlacementChildren->first();
                    $newRoot->update([
                        'placement_parent_id' => null,
                        'placement_position' => null,
                    ]);
                    $promotedRootCode = $newRoot->customer_id ?? "ID#{$newRoot->id}";

                    // Re-parent remaining placement children under free slots of the new root or as secondary roots:
                    $remainingChildren = $directPlacementChildren->slice(1);
                    $occupied = Member::where('placement_parent_id', $newRoot->id)
                        ->pluck('placement_position')
                        ->all();
                    $availablePositions = array_values(array_diff(Member::POSITIONS, $occupied));

                    foreach ($remainingChildren as $child) {
                        if (! empty($availablePositions)) {
                            $child->update([
                                'placement_parent_id' => $newRoot->id,
                                'placement_position' => array_shift($availablePositions),
                            ]);
                        } else {
                            $child->update([
                                'placement_parent_id' => null,
                                'placement_position' => null,
                            ]);
                        }
                    }
                } else {
                    // Regular member with a placement parent
                    $oldParentId = $member->placement_parent_id;
                    $oldPosition = $member->placement_position;

                    // Promote first child to take the vacated slot under old parent
                    $primaryChild = $directPlacementChildren->first();
                    $primaryChild->update([
                        'placement_parent_id' => $oldParentId,
                        'placement_position' => $oldPosition,
                    ]);

                    // Place remaining children under primary child if free slots exist
                    $remainingChildren = $directPlacementChildren->slice(1);
                    $occupied = Member::where('placement_parent_id', $primaryChild->id)
                        ->pluck('placement_position')
                        ->all();
                    $availablePositions = array_values(array_diff(Member::POSITIONS, $occupied));

                    foreach ($remainingChildren as $child) {
                        if (! empty($availablePositions)) {
                            $child->update([
                                'placement_parent_id' => $primaryChild->id,
                                'placement_position' => array_shift($availablePositions),
                            ]);
                        } else {
                            $child->update([
                                'placement_parent_id' => null,
                                'placement_position' => null,
                            ]);
                        }
                    }
                }
            }

            // 3. CLEAN UP CHILD TABLES WITH RESTRICT-ON-DELETE CONSTRAINTS
            // A. Placement movements
            DB::table('mlm_placement_movements')->where('member_id', $memberId)->delete();
            DB::table('mlm_placement_movements')->where('old_parent_id', $memberId)->update(['old_parent_id' => null]);
            DB::table('mlm_placement_movements')->where('new_parent_id', $memberId)->update(['new_parent_id' => null]);

            // B. KYC histories and files
            if ($member->cancelled_cheque_path && \Illuminate\Support\Facades\Storage::disk('local')->exists($member->cancelled_cheque_path)) {
                \Illuminate\Support\Facades\Storage::disk('local')->delete($member->cancelled_cheque_path);
            }
            if ($member->profile_photo_path && \Illuminate\Support\Facades\Storage::disk('local')->exists($member->profile_photo_path)) {
                \Illuminate\Support\Facades\Storage::disk('local')->delete($member->profile_photo_path);
            }
            DB::table('mlm_kyc_histories')->where('member_id', $memberId)->delete();

            // C. Calculation runs & Income ledgers
            $calculationRunIds = DB::table('mlm_calculation_runs')
                ->where('purchasing_member_id', $memberId)
                ->pluck('id');
            if ($calculationRunIds->isNotEmpty()) {
                DB::table('mlm_income_ledgers')->whereIn('calculation_run_id', $calculationRunIds)->delete();
                DB::table('mlm_calculation_runs')->whereIn('id', $calculationRunIds)->delete();
            }
            DB::table('mlm_income_ledgers')
                ->where('member_id', $memberId)
                ->orWhere('purchasing_member_id', $memberId)
                ->delete();

            // D. Payout lines & Cashback items
            DB::table('mlm_payout_lines')->where('member_id', $memberId)->delete();
            DB::table('cashback_payout_batch_items')->where('member_id', $memberId)->delete();
            DB::table('cashback_eligibilities')->where('member_id', $memberId)->delete();
            DB::table('cashback_adjustments')->where('member_id', $memberId)->delete();

            // E. Activity logs
            DB::table('activity_logs')
                ->where('subject_type', Member::class)
                ->where('subject_id', $memberId)
                ->delete();

            // 4. DETACH USER RECORD
            if ($user) {
                $user->update(['mlm_member_id' => null]);
                if ($deleteUser) {
                    $hasOrders = DB::table('orders')->where('user_id', $user->id)->exists();
                    if (! $hasOrders) {
                        try {
                            $user->delete();
                        } catch (\Throwable) {
                            // Retain user if constrained by any other table
                        }
                    }
                }
            }

            // 5. DELETE THE MEMBER RECORD
            $member->delete();

            // 6. RECORD AUDIT TRAIL
            ActivityLogService::log(
                'delete',
                'mlm_members',
                "Super Admin {$admin->name} deleted MLM member {$customerCode} ({$memberName})" . ($isRootLeader ? ' [ROOT LEADER REMOVED & MATRIX RE-ANCHORED]' : ''),
                null,
                [
                    'member_id' => $memberId,
                    'customer_code' => $customerCode,
                    'name' => $memberName,
                    'was_root' => $isRootLeader,
                    'promoted_root' => $promotedRootCode,
                    'deleted_by' => $admin->id,
                ],
                null
            );

            return [
                'success' => true,
                'is_root' => $isRootLeader,
                'name' => $memberName,
                'code' => $customerCode,
                'promoted_root' => $promotedRootCode,
            ];
        });
    }

    private function resolveCustomer(?int $userId): ?User
    {
        if (! $userId) {
            return null;
        }

        $user = User::query()
            ->whereKey($userId)
            ->whereHas('role', fn ($query) => $query->where('name', 'Customer'))
            ->with('mlmMember')
            ->first();

        if (! $user || $user->mlmMember) {
            throw ValidationException::withMessages([
                'user_id' => 'Select an unassigned Customer account.',
            ]);
        }

        return $user;
    }

    private function storeProfilePhoto(Member $member, ?UploadedFile $profilePhoto): void
    {
        if ($profilePhoto) {
            $member->update([
                'profile_photo_path' => $profilePhoto->store('mlm/profile-photos', 'local'),
            ]);
        }
    }

    private function snapshot(Member $member): array
    {
        $member->loadMissing(['user', 'sponsor.user']);

        return [
            'member_id' => $member->customer_id,
            'name' => $member->user?->name,
            'mobile' => $member->mobile,
            'email' => $member->user?->email,
            'sponsor_member_id' => $member->sponsor?->customer_id,
            'status' => $member->status,
            'kyc_status' => $member->kyc_status,
        ];
    }
}
