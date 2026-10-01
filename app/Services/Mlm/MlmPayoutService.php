<?php

namespace App\Services\Mlm;

use App\Models\Member;
use App\Models\MlmCalculationAudit;
use App\Models\MlmCalculationRule;
use App\Models\MlmCalculationRun;
use App\Models\MlmIncomeLedger;
use App\Models\MlmPayoutLine;
use App\Models\MlmPayoutCycle;
use App\Models\User;
use App\Support\MlmDecimal;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class MlmPayoutService
{
    public function createWeeklyCycle(Carbon|string $date, User $admin, ?string $note = null): MlmPayoutCycle
    {
        $periodStart = Carbon::parse($date)->startOfWeek(Carbon::MONDAY)->startOfDay();
        $periodEnd = $periodStart->copy()->addDays(6)->endOfDay();

        return DB::transaction(function () use ($periodStart, $periodEnd, $admin, $note): MlmPayoutCycle {
            $cycle = MlmPayoutCycle::query()
                ->whereDate('period_start', $periodStart->toDateString())
                ->whereDate('period_end', $periodEnd->toDateString())
                ->lockForUpdate()
                ->first();

            if ($cycle && $cycle->status !== MlmPayoutCycle::STATUS_PENDING_CALCULATION) {
                return $cycle->fresh(['ledgers']);
            }

            $cycle ??= MlmPayoutCycle::create([
                'period_start' => $periodStart->toDateString(),
                'period_end' => $periodEnd->toDateString(),
                'cycle_reference' => 'MLM-'.$periodStart->format('Ymd').'-'.$periodEnd->format('Ymd'),
                'status' => MlmPayoutCycle::STATUS_PENDING_CALCULATION,
                'total_amount' => MlmDecimal::normalize('0'),
                'net_payable' => MlmDecimal::normalize('0'),
                'ledger_count' => 0,
                'calculation_date' => now(),
                'admin_note' => $note ? trim($note) : null,
                'created_by' => $admin->id,
            ]);

            $ledgers = MlmIncomeLedger::query()
                ->with('member')
                ->whereBetween('transaction_date', [$periodStart, $periodEnd])
                ->whereNull('payout_cycle_id')
                ->whereIn('status', [MlmIncomeLedger::STATUS_CALCULATED])
                ->whereIn('income_type', [
                    MlmIncomeLedger::TYPE_OWN_PURCHASE,
                    MlmIncomeLedger::TYPE_REFERRAL,
                    MlmIncomeLedger::TYPE_LEVEL,
                    MlmIncomeLedger::TYPE_ADJUSTMENT,
                    MlmIncomeLedger::TYPE_REVERSAL,
                ])
                ->whereHas('member', fn ($query) => $query->where('status', Member::STATUS_ACTIVE))
                ->lockForUpdate()
                ->get();

            $total = MlmDecimal::normalize('0');
            $gross = MlmDecimal::normalize('0');
            $adjustments = MlmDecimal::normalize('0');
            $grouped = $ledgers->groupBy('member_id');
            foreach ($ledgers as $ledger) {
                $total = MlmDecimal::add($total, (string) $ledger->calculated_amount);
                if (in_array($ledger->income_type, [MlmIncomeLedger::TYPE_ADJUSTMENT, MlmIncomeLedger::TYPE_REVERSAL], true)) {
                    $adjustments = MlmDecimal::add($adjustments, (string) $ledger->calculated_amount);
                } else {
                    $gross = MlmDecimal::add($gross, (string) $ledger->calculated_amount);
                }
                $ledger->update([
                    'payout_cycle_id' => $cycle->id,
                    'status' => MlmIncomeLedger::STATUS_PENDING_APPROVAL,
                ]);
            }

            foreach ($grouped as $memberId => $memberLedgers) {
                $lineGross = MlmDecimal::normalize('0');
                $lineAdjustments = MlmDecimal::normalize('0');
                $lineNet = MlmDecimal::normalize('0');
                foreach ($memberLedgers as $ledger) {
                    $lineNet = MlmDecimal::add($lineNet, (string) $ledger->calculated_amount);
                    if (in_array($ledger->income_type, [MlmIncomeLedger::TYPE_ADJUSTMENT, MlmIncomeLedger::TYPE_REVERSAL], true)) {
                        $lineAdjustments = MlmDecimal::add($lineAdjustments, (string) $ledger->calculated_amount);
                    } else {
                        $lineGross = MlmDecimal::add($lineGross, (string) $ledger->calculated_amount);
                    }
                }

                MlmPayoutLine::updateOrCreate(
                    ['payout_cycle_id' => $cycle->id, 'member_id' => $memberId],
                    [
                        'gross_income' => $lineGross,
                        'adjustment_amount' => $lineAdjustments,
                        'net_payable' => $lineNet,
                        'ledger_count' => $memberLedgers->count(),
                        'status' => MlmPayoutCycle::STATUS_PENDING_APPROVAL,
                    ]
                );
            }

            $cycle->update([
                'status' => $ledgers->isEmpty()
                    ? MlmPayoutCycle::STATUS_PENDING_CALCULATION
                    : MlmPayoutCycle::STATUS_PENDING_APPROVAL,
                'total_amount' => $total,
                'net_payable' => $total,
                'gross_income' => $gross,
                'adjustment_amount' => $adjustments,
                'total_members' => $grouped->count(),
                'ledger_count' => $ledgers->count(),
                'calculation_date' => now(),
            ]);

            return $cycle->fresh(['ledgers']);
        });
    }

    public function approve(MlmPayoutCycle $cycle, User $admin): MlmPayoutCycle
    {
        return $this->transition(
            $cycle,
            [MlmPayoutCycle::STATUS_PENDING_APPROVAL],
            MlmPayoutCycle::STATUS_APPROVED,
            $admin,
            ['approved_by' => $admin->id, 'approved_at' => now()]
        );
    }

    public function startProcessing(MlmPayoutCycle $cycle, User $admin): MlmPayoutCycle
    {
        return $this->transition(
            $cycle,
            [MlmPayoutCycle::STATUS_APPROVED, MlmPayoutCycle::STATUS_FAILED],
            MlmPayoutCycle::STATUS_PROCESSING,
            $admin,
            ['processed_by' => $admin->id, 'processed_at' => now()]
        );
    }

    public function markPaid(
        MlmPayoutCycle $cycle,
        User $admin,
        string $paymentReference,
        Carbon|string $paymentDate,
        ?string $note = null,
        ?UploadedFile $paymentProof = null
    ): MlmPayoutCycle {
        if (trim($paymentReference) === '') {
            $this->fail('payment_reference', 'Payment reference is required before marking a cycle paid.');
        }

        return DB::transaction(function () use ($cycle, $admin, $paymentReference, $paymentDate, $note, $paymentProof): MlmPayoutCycle {
            $lockedCycle = MlmPayoutCycle::query()->lockForUpdate()->findOrFail($cycle->id);
            $this->assertStatus($lockedCycle, [MlmPayoutCycle::STATUS_PROCESSING]);

            $proofPath = $paymentProof?->store('mlm/payout-proofs', 'local');

            $lockedCycle->update([
                'status' => MlmPayoutCycle::STATUS_PAID,
                'payment_reference' => trim($paymentReference),
                'payment_date' => Carbon::parse($paymentDate),
                'admin_note' => $note !== null ? trim($note) : $lockedCycle->admin_note,
                'payment_proof_path' => $proofPath ?: $lockedCycle->payment_proof_path,
                'paid_by' => $admin->id,
                'paid_at' => now(),
            ]);
            $this->updateCycleLedgers($lockedCycle, MlmIncomeLedger::STATUS_PAID);
            $lockedCycle->lines()->update(['status' => MlmPayoutCycle::STATUS_PAID]);

            return $lockedCycle->fresh(['ledgers']);
        });
    }

    public function markFailed(MlmPayoutCycle $cycle, User $admin, ?string $note = null): MlmPayoutCycle
    {
        return DB::transaction(function () use ($cycle, $admin, $note): MlmPayoutCycle {
            $lockedCycle = MlmPayoutCycle::query()->lockForUpdate()->findOrFail($cycle->id);
            $this->assertStatus($lockedCycle, [MlmPayoutCycle::STATUS_PROCESSING]);
            $lockedCycle->update([
                'status' => MlmPayoutCycle::STATUS_FAILED,
                'admin_note' => $note !== null ? trim($note) : $lockedCycle->admin_note,
                'processed_by' => $admin->id,
                'processed_at' => now(),
            ]);
            $this->updateCycleLedgers($lockedCycle, MlmIncomeLedger::STATUS_FAILED);
            $lockedCycle->lines()->update(['status' => MlmPayoutCycle::STATUS_FAILED]);

            return $lockedCycle->fresh(['ledgers']);
        });
    }

    public function hold(MlmPayoutCycle $cycle, string $reason, User $admin): MlmPayoutCycle
    {
        if (trim($reason) === '') {
            $this->fail('reason', 'A hold reason is required.');
        }

        return DB::transaction(function () use ($cycle, $reason, $admin): MlmPayoutCycle {
            $lockedCycle = MlmPayoutCycle::query()->lockForUpdate()->findOrFail($cycle->id);
            $this->assertStatus($lockedCycle, [
                MlmPayoutCycle::STATUS_PENDING_APPROVAL,
                MlmPayoutCycle::STATUS_APPROVED,
                MlmPayoutCycle::STATUS_PROCESSING,
                MlmPayoutCycle::STATUS_FAILED,
            ]);
            $lockedCycle->update([
                'status' => MlmPayoutCycle::STATUS_ON_HOLD,
                'hold_reason' => trim($reason),
                'on_hold_by' => $admin->id,
                'on_hold_at' => now(),
            ]);
            $lockedCycle->ledgers()->whereNotIn('status', [MlmIncomeLedger::STATUS_REVERSED])->update(['status' => MlmIncomeLedger::STATUS_PENDING_APPROVAL]);
            $lockedCycle->lines()->update(['status' => MlmPayoutCycle::STATUS_ON_HOLD]);

            return $lockedCycle->fresh(['ledgers', 'lines']);
        });
    }

    public function releaseHold(MlmPayoutCycle $cycle, User $admin): MlmPayoutCycle
    {
        return $this->transition(
            $cycle,
            [MlmPayoutCycle::STATUS_ON_HOLD],
            MlmPayoutCycle::STATUS_APPROVED,
            $admin,
            ['hold_reason' => null]
        );
    }

    public function reverse(MlmIncomeLedger $ledger, User $admin, ?string $reason = null): MlmIncomeLedger
    {
        return DB::transaction(function () use ($ledger, $admin): MlmIncomeLedger {
            $original = MlmIncomeLedger::query()->lockForUpdate()->findOrFail($ledger->id);
            if ($original->income_type === MlmIncomeLedger::TYPE_REVERSAL || $original->status === MlmIncomeLedger::STATUS_REVERSED) {
                $this->fail('ledger', 'This ledger entry has already been reversed.');
            }

            if (! in_array($original->status, [MlmIncomeLedger::STATUS_PAID, MlmIncomeLedger::STATUS_FAILED], true)) {
                $this->fail('ledger', 'Only paid or failed ledger entries can be reversed.');
            }

            $reversal = MlmIncomeLedger::create([
                'calculation_run_id' => $original->calculation_run_id,
                'member_id' => $original->member_id,
                'purchasing_member_id' => $original->purchasing_member_id,
                'rule_version_id' => $original->rule_version_id,
                'source_transaction_reference' => substr($original->source_transaction_reference.'-REVERSAL-'.$original->id, 0, 150),
                'transaction_date' => now(),
                'income_type' => MlmIncomeLedger::TYPE_REVERSAL,
                'level' => $original->level,
                'eligible_amount' => MlmDecimal::negate((string) $original->eligible_amount),
                'pv_rate' => $original->pv_rate,
                'rate' => MlmDecimal::negate((string) $original->rate),
                'pv' => MlmDecimal::negate((string) $original->pv),
                'calculated_amount' => MlmDecimal::negate((string) $original->calculated_amount),
                'status' => MlmIncomeLedger::STATUS_CALCULATED,
                'placement_path_snapshot' => $original->placement_path_snapshot,
                'reversal_of_id' => $original->id,
            ]);

            $original->update(['status' => MlmIncomeLedger::STATUS_REVERSED]);
            MlmCalculationAudit::create([
                'calculation_run_id' => $original->calculation_run_id,
                'event' => 'ledger_reversed',
                'payload' => ['ledger_id' => $original->id, 'reversal_id' => $reversal->id, 'reason' => trim($reason ?? 'Manual MLM payout reversal.')],
                'created_by' => $admin->id,
            ]);

            return $reversal->fresh(['member.user', 'reversalOf']);
        });
    }

    public function createAdjustment(
        Member $member,
        string $eligibleAmount,
        string $pv,
        string $rate,
        string $calculatedAmount,
        int $level,
        string $reference,
        Carbon|string $date,
        MlmCalculationRule $rule,
        User $admin
    ): MlmIncomeLedger {
        foreach ([$eligibleAmount, $pv, $rate, $calculatedAmount] as $value) {
            try {
                MlmDecimal::normalizeSigned($value);
            } catch (\InvalidArgumentException) {
                $this->fail('adjustment', 'Adjustment financial values must be exact decimals.');
            }
        }

        if ($level < 0 || $level > 19 || trim($reference) === '') {
            $this->fail('adjustment', 'Adjustment level or reference is invalid.');
        }

        return DB::transaction(function () use ($member, $eligibleAmount, $pv, $rate, $calculatedAmount, $level, $reference, $date, $rule, $admin): MlmIncomeLedger {
            $reference = trim($reference);
            $idempotencyKey = hash('sha256', 'adjustment|'.$member->id.'|'.$reference);
            $existing = MlmCalculationRun::query()->where('idempotency_key', $idempotencyKey)->first();
            if ($existing) {
                return MlmIncomeLedger::query()->where('calculation_run_id', $existing->id)->firstOrFail();
            }

            $normalizedAmount = MlmDecimal::normalizeSigned($eligibleAmount);
            $normalizedPv = MlmDecimal::normalizeSigned($pv);
            $normalizedRate = MlmDecimal::normalizeSigned($rate);
            $normalizedCalculated = MlmDecimal::normalizeSigned($calculatedAmount);
            $run = MlmCalculationRun::create([
                'purchasing_member_id' => $member->id,
                'rule_version_id' => $rule->id,
                'source_transaction_reference' => $reference,
                'idempotency_key' => $idempotencyKey,
                'transaction_date' => Carbon::parse($date),
                'eligible_amount' => $normalizedAmount,
                'total_pv' => $normalizedPv,
                'total_income' => $normalizedCalculated,
                'status' => 'adjustment',
                'placement_path_snapshot' => [],
                'calculated_by' => $admin->id,
            ]);
            $ledger = MlmIncomeLedger::create([
                'calculation_run_id' => $run->id,
                'member_id' => $member->id,
                'purchasing_member_id' => $member->id,
                'rule_version_id' => $rule->id,
                'source_transaction_reference' => $reference,
                'transaction_date' => Carbon::parse($date),
                'income_type' => MlmIncomeLedger::TYPE_ADJUSTMENT,
                'level' => $level,
                'eligible_amount' => $normalizedAmount,
                'pv_rate' => $normalizedRate,
                'rate' => $normalizedRate,
                'pv' => $normalizedPv,
                'calculated_amount' => $normalizedCalculated,
                'status' => MlmIncomeLedger::STATUS_CALCULATED,
                'placement_path_snapshot' => [],
            ]);
            MlmCalculationAudit::create([
                'calculation_run_id' => $run->id,
                'event' => 'adjustment_created',
                'payload' => ['ledger_id' => $ledger->id, 'reference' => $reference],
                'created_by' => $admin->id,
            ]);

            return $ledger->fresh(['member.user', 'ruleVersion']);
        });
    }

    private function transition(MlmPayoutCycle $cycle, array $from, string $to, User $admin, array $extra): MlmPayoutCycle
    {
        return DB::transaction(function () use ($cycle, $from, $to, $extra): MlmPayoutCycle {
            $lockedCycle = MlmPayoutCycle::query()->lockForUpdate()->findOrFail($cycle->id);
            $this->assertStatus($lockedCycle, $from);
            $lockedCycle->update(['status' => $to, ...$extra]);
            if ($to === MlmPayoutCycle::STATUS_APPROVED) {
                $this->updateCycleLedgers($lockedCycle, MlmIncomeLedger::STATUS_APPROVED);
            } elseif ($to === MlmPayoutCycle::STATUS_PROCESSING) {
                $this->updateCycleLedgers($lockedCycle, MlmIncomeLedger::STATUS_PROCESSING);
            }
            $lockedCycle->lines()->update(['status' => $to]);

            return $lockedCycle->fresh(['ledgers']);
        });
    }

    private function updateCycleLedgers(MlmPayoutCycle $cycle, string $status): void
    {
        $cycle->ledgers()->whereNotIn('status', [MlmIncomeLedger::STATUS_REVERSED])->update(['status' => $status]);
    }

    private function assertStatus(MlmPayoutCycle $cycle, array $allowed): void
    {
        if (! in_array($cycle->status, $allowed, true)) {
            $this->fail('status', 'The payout cycle cannot transition from its current status.');
        }
    }

    private function fail(string $field, string $message): never
    {
        throw ValidationException::withMessages([$field => $message]);
    }
}
