<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $member = $this->mlmMember;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'role' => $this->role?->name ?? 'Customer',
            'status' => $this->status ?? 'active',
            'mlm_member_id' => $this->mlm_member_id ?? $member?->customer_id,
            'member_status' => $member?->status,
            'kyc_status' => $member?->kyc_status,
            'direct_referral_count' => $member
                ? ($member->relationLoaded('sponsoredMembers')
                    ? $member->sponsoredMembers->count()
                    : $member->sponsoredMembers()->count())
                : 0,
            'sponsor' => $member?->sponsor ? [
                'id' => $member->sponsor->id,
                'customer_id' => $member->sponsor->customer_id,
                'name' => $member->sponsor->user?->name,
            ] : null,
            'created_at' => $this->created_at ? $this->created_at->toIso8601String() : null,
        ];
    }
}
