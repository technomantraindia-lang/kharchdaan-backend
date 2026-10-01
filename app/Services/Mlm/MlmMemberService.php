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
