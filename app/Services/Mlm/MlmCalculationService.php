<?php

namespace App\Services\Mlm;

use App\Models\Member;
use App\Models\MlmCalculationAudit;
use App\Models\MlmCalculationRule;
use App\Models\MlmCalculationRun;
use App\Models\MlmIncomeLedger;
use App\Models\Order;
use App\Models\PlacementMovement;
use App\Models\Refund;
use App\Models\User;
use App\Support\MlmDecimal;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MlmCalculationService
{
    public function preview(
        Member $purchasingMember,
        string $eligibleAmount,
        string $transactionReference,
        Carbon|string $transactionDate,
        MlmCalculationRule $rule
    ): array {
        $date = Carbon::parse($transactionDate);
        $amount = $this->validateInputs($purchasingMember, $eligibleAmount, $transactionReference, $date, $rule);
        $calculation = $this->buildCalculation($purchasingMember, $amount, trim($transactionReference), $date, $rule);

        return [
            'idempotency_key' => $this->idempotencyKey($purchasingMember, $transactionReference),
            'transaction_reference' => trim($transactionReference),
            'transaction_date' => $date->toDateTimeString(),
            'eligible_amount' => $amount,
            'rule_version' => $rule->version,
            'purchasing_member' => [
                'customer_id' => $purchasingMember->customer_id,
                'name' => $purchasingMember->user?->name,
            ],
            ...$calculation,
        ];
    }

    public function calculate(
        Member $purchasingMember,
        string $eligibleAmount,
        string $transactionReference,
        Carbon|string $transactionDate,
        MlmCalculationRule $rule,
        ?User $calculatedBy = null,
        ?Order $sourceOrder = null
    ): MlmCalculationRun {
        $date = Carbon::parse($transactionDate);
        $amount = $this->validateInputs($purchasingMember, $eligibleAmount, $transactionReference, $date, $rule);
        $reference = trim($transactionReference);
        $idempotencyKey = $this->idempotencyKey($purchasingMember, $reference);

        $startedAt = now();

        return DB::transaction(function () use ($purchasingMember, $amount, $reference, $date, $rule, $calculatedBy, $idempotencyKey, $sourceOrder, $startedAt): MlmCalculationRun {
            $existing = MlmCalculationRun::query()
                ->when($sourceOrder, fn ($query) => $query->where('order_id', $sourceOrder->id))
                ->orWhere('idempotency_key', $idempotencyKey)
                ->lockForUpdate()
                ->first();
            if ($existing && $existing->status !== 'failed') {
                return $existing->fresh(['incomeLedgers', 'audits']);
            }

            $calculation = $this->buildCalculation($purchasingMember, $amount, $reference, $date, $rule);
            $runAttributes = [
                'purchasing_member_id' => $purchasingMember->id,
                'order_id' => $sourceOrder?->id,
                'rule_version_id' => $rule->id,
                'source_transaction_reference' => $reference,
                'idempotency_key' => $idempotencyKey,
                'transaction_date' => $date,
                'eligible_amount' => $amount,
                'total_pv' => $calculation['total_pv'],
                'total_income' => $calculation['total_income'],
                'status' => 'calculated',
                'placement_path_snapshot' => $calculation['placement_path'],
                'calculated_by' => $calculatedBy?->id,
                'processing_started_at' => $startedAt,
                'processed_at' => now(),
                'processing_time_ms' => max(0, (int) round(microtime(true) * 1000 - $startedAt->getPreciseTimestamp(3))),
                'error_message' => null,
            ];
            $run = $existing ?: MlmCalculationRun::create($runAttributes);
            if ($existing) {
                $run->update($runAttributes);
            }

            foreach ($calculation['lines'] as $line) {
                MlmIncomeLedger::create([
                    'calculation_run_id' => $run->id,
                    'member_id' => $line['member_id'],
                    'purchasing_member_id' => $purchasingMember->id,
                    'rule_version_id' => $rule->id,
                    'source_transaction_reference' => $reference,
                    'transaction_date' => $date,
                    'income_type' => $line['income_type'],
                    'level' => $line['level'],
                    'eligible_amount' => $amount,
                    'pv_rate' => $line['pv_rate'],
                    'rate' => $line['rate'],
                    'pv' => $line['pv'],
                    'calculated_amount' => $line['calculated_amount'],
                    'status' => 'calculated',
                    'placement_path_snapshot' => $calculation['placement_path'],
                    'ledger_entry_key' => hash('sha256', 'mlm-original-line|run:'.$run->id.'|member:'.$line['member_id'].'|level:'.$line['level'].'|type:'.$line['income_type']),
                ]);
            }

            MlmCalculationAudit::create([
                'calculation_run_id' => $run->id,
                'event' => 'calculated',
                'payload' => [
                    'source_transaction_reference' => $reference,
                    'eligible_amount' => $amount,
                    'rule_version' => $rule->version,
                    'total_pv' => $calculation['total_pv'],
                    'total_income' => $calculation['total_income'],
                    'line_count' => count($calculation['lines']),
                ],
                'created_by' => $calculatedBy?->id,
            ]);

            return $run->fresh(['incomeLedgers', 'audits', 'ruleVersion', 'purchasingMember.user']);
        });
    }

    public function reverseCalculation(MlmCalculationRun $run, ?User $admin = null, string $reason = 'Order no longer eligible'): MlmCalculationRun
    {
        return DB::transaction(function () use ($run, $admin, $reason): MlmCalculationRun {
            $lockedRun = MlmCalculationRun::query()->lockForUpdate()->findOrFail($run->id);
            if ($lockedRun->status === 'reversed') {
                return $lockedRun->fresh(['incomeLedgers', 'audits']);
            }

            $originalLedgers = MlmIncomeLedger::query()
                ->where('calculation_run_id', $lockedRun->id)
                ->where('income_type', '!=', MlmIncomeLedger::TYPE_REVERSAL)
                ->lockForUpdate()
                ->get();

            foreach ($originalLedgers as $original) {
                $eventKey = hash('sha256', 'mlm-order-reversal|run:'.$lockedRun->id.'|ledger:'.$original->id);
                if (MlmIncomeLedger::query()->where('reversal_event_key', $eventKey)->exists()) {
                    continue;
                }

                $existingReversals = MlmIncomeLedger::query()
                    ->where('reversal_of_id', $original->id)
                    ->where('income_type', MlmIncomeLedger::TYPE_REVERSAL)
                    ->get();
                $remainingEligible = MlmDecimal::subtract(
                    (string) $original->eligible_amount,
                    $existingReversals->reduce(
                        fn (string $total, MlmIncomeLedger $ledger): string => MlmDecimal::add($total, MlmDecimal::absolute((string) $ledger->eligible_amount)),
                        MlmDecimal::normalize('0')
                    )
                );
                $remainingPv = MlmDecimal::subtract(
                    (string) $original->pv,
                    $existingReversals->reduce(
                        fn (string $total, MlmIncomeLedger $ledger): string => MlmDecimal::add($total, MlmDecimal::absolute((string) $ledger->pv)),
                        MlmDecimal::normalize('0')
                    )
                );
                $remainingIncome = MlmDecimal::subtract(
                    (string) $original->calculated_amount,
                    $existingReversals->reduce(
                        fn (string $total, MlmIncomeLedger $ledger): string => MlmDecimal::add($total, MlmDecimal::absolute((string) $ledger->calculated_amount)),
                        MlmDecimal::normalize('0')
                    )
                );
                if (bccomp($remainingIncome, '0', MlmDecimal::SCALE) <= 0) {
                    continue;
                }

                MlmIncomeLedger::create([
                    'calculation_run_id' => $lockedRun->id,
                    'member_id' => $original->member_id,
                    'purchasing_member_id' => $original->purchasing_member_id,
                    'rule_version_id' => $original->rule_version_id,
                    'source_transaction_reference' => substr($original->source_transaction_reference.'-REVERSAL-'.$original->id, 0, 150),
                    'transaction_date' => now(),
                    'income_type' => MlmIncomeLedger::TYPE_REVERSAL,
                    'level' => $original->level,
                    'eligible_amount' => MlmDecimal::negate($remainingEligible),
                    'pv_rate' => $original->pv_rate,
                    'rate' => $original->rate,
                    'pv' => MlmDecimal::negate($remainingPv),
                    'calculated_amount' => MlmDecimal::negate($remainingIncome),
                    'status' => MlmIncomeLedger::STATUS_REVERSED,
                    'placement_path_snapshot' => $original->placement_path_snapshot,
                    'reversal_of_id' => $original->id,
                    'reversal_event_key' => $eventKey,
                    'ledger_entry_key' => $eventKey,
                ]);
            }

            $lockedRun->update(['status' => 'reversed', 'error_message' => $reason]);
            MlmCalculationAudit::create([
                'calculation_run_id' => $lockedRun->id,
                'event' => 'order_calculation_reversed',
                'payload' => ['reason' => $reason, 'order_id' => $lockedRun->order_id],
                'created_by' => $admin?->id,
            ]);

            return $lockedRun->fresh(['incomeLedgers', 'audits']);
        });
    }

    public function reconcileApprovedRefunds(MlmCalculationRun $run, ?User $actor = null): MlmCalculationRun
    {
        return DB::transaction(function () use ($run, $actor): MlmCalculationRun {
            $lockedRun = MlmCalculationRun::query()->lockForUpdate()->findOrFail($run->id);
            $originalEligible = MlmDecimal::normalize((string) $lockedRun->eligible_amount);
            if (! MlmDecimal::isPositive($originalEligible)) {
                return $lockedRun->fresh(['incomeLedgers', 'audits']);
            }

            $originalLedgers = MlmIncomeLedger::query()
                ->where('calculation_run_id', $lockedRun->id)
                ->where('income_type', '!=', MlmIncomeLedger::TYPE_REVERSAL)
                ->lockForUpdate()
                ->get();
            $refunded = MlmDecimal::normalize('0');

            $refunds = Refund::query()
                ->where('order_id', $lockedRun->order_id)
                ->whereIn('status', ['approved', 'completed'])
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            foreach ($refunds as $refund) {
                $refunded = MlmDecimal::minimum(
                    $originalEligible,
                    MlmDecimal::add($refunded, MlmDecimal::normalize((string) $refund->amount))
                );

                foreach ($originalLedgers as $original) {
                    $eventKey = hash('sha256', 'mlm-refund-event|run:'.$lockedRun->id.'|refund:'.$refund->id.'|ledger:'.$original->id);
                    if (MlmIncomeLedger::query()->where('reversal_event_key', $eventKey)->exists()) {
                        continue;
                    }

                    $ratio = MlmDecimal::divide($refunded, $originalEligible);
                    $targetEligible = MlmDecimal::multiply((string) $original->eligible_amount, $ratio);
                    $targetPv = MlmDecimal::multiply((string) $original->pv, $ratio);
                    $targetIncome = MlmDecimal::multiply((string) $original->calculated_amount, $ratio);
                    $existingReversals = MlmIncomeLedger::query()
                        ->where('reversal_of_id', $original->id)
                        ->where('income_type', MlmIncomeLedger::TYPE_REVERSAL)
                        ->get();
                    $eligibleReversal = MlmDecimal::negate(MlmDecimal::subtract(
                        $targetEligible,
                        $existingReversals->reduce(
                            fn (string $total, MlmIncomeLedger $ledger): string => MlmDecimal::add($total, MlmDecimal::absolute((string) $ledger->eligible_amount)),
                            MlmDecimal::normalize('0')
                        )
                    ));
                    $pvReversal = MlmDecimal::negate(MlmDecimal::subtract(
                        $targetPv,
                        $existingReversals->reduce(
                            fn (string $total, MlmIncomeLedger $ledger): string => MlmDecimal::add($total, MlmDecimal::absolute((string) $ledger->pv)),
                            MlmDecimal::normalize('0')
                        )
                    ));
                    $incomeReversal = MlmDecimal::negate(MlmDecimal::subtract(
                        $targetIncome,
                        $existingReversals->reduce(
                            fn (string $total, MlmIncomeLedger $ledger): string => MlmDecimal::add($total, MlmDecimal::absolute((string) $ledger->calculated_amount)),
                            MlmDecimal::normalize('0')
                        )
                    ));

                    MlmIncomeLedger::create([
                        'calculation_run_id' => $lockedRun->id,
                        'member_id' => $original->member_id,
                        'purchasing_member_id' => $original->purchasing_member_id,
                        'rule_version_id' => $original->rule_version_id,
                        'source_transaction_reference' => substr($original->source_transaction_reference.'-REFUND-'.$refund->refund_num, 0, 150),
                        'transaction_date' => $refund->processed_at ?? now(),
                        'income_type' => MlmIncomeLedger::TYPE_REVERSAL,
                        'level' => $original->level,
                        'eligible_amount' => $eligibleReversal,
                        'pv_rate' => $original->pv_rate,
                        'rate' => $original->rate,
                        'pv' => $pvReversal,
                        'calculated_amount' => $incomeReversal,
                        'status' => MlmIncomeLedger::STATUS_REVERSED,
                        'placement_path_snapshot' => $original->placement_path_snapshot,
                        'reversal_of_id' => $original->id,
                        'source_refund_id' => $refund->id,
                        'reversal_event_key' => $eventKey,
                        'ledger_entry_key' => $eventKey,
                    ]);
                }

                MlmCalculationAudit::create([
                    'calculation_run_id' => $lockedRun->id,
                    'event' => 'refund_reconciled',
                    'payload' => [
                        'refund_id' => $refund->id,
                        'refund_reference' => $refund->refund_num,
                        'refunded_eligible_amount' => $refunded,
                        'original_eligible_amount' => $originalEligible,
                    ],
                    'created_by' => $actor?->id ?? $refund->processed_by,
                ]);
            }

            if (bccomp($refunded, $originalEligible, MlmDecimal::SCALE) >= 0) {
                $lockedRun->update(['status' => 'reversed', 'error_message' => 'Fully reversed by approved refunds.']);
            }

            return $lockedRun->fresh(['incomeLedgers', 'audits']);
        });
    }

    private function buildCalculation(
        Member $purchasingMember,
        string $amount,
        string $reference,
        Carbon $date,
        MlmCalculationRule $rule
    ): array {
        $pathIds = $this->placementPathAt($purchasingMember, $date);
        if (count($pathIds) > 20) {
            $this->fail('purchasing_member_id', 'The placement path exceeds the supported Level 19 range.');
        }

        $members = Member::query()->with('user')->whereIn('id', $pathIds)->get()->keyBy('id');
        if ($members->count() !== count($pathIds)) {
            $this->fail('purchasing_member_id', 'The placement path contains an invalid member.');
        }

        $placementPath = collect($pathIds)->map(fn (int $id, int $index): array => [
            'id' => $id,
            'customer_id' => $members[$id]->customer_id,
            'name' => $members[$id]->user?->name,
            'level' => $index,
        ])->values()->all();

        $lines = [];
        $totalPv = MlmDecimal::normalize('0');
        $totalIncome = MlmDecimal::normalize('0');
        foreach (array_reverse($pathIds) as $reverseIndex => $memberId) {
            $level = $reverseIndex;
            if ($level > $rule->low_level_end) {
                break;
            }

            $beneficiary = $members[$memberId];
            if ($beneficiary->status !== Member::STATUS_ACTIVE) {
                continue;
            }

            $pvRate = $level >= $rule->high_level_start && $level <= $rule->high_level_end
                ? (string) $rule->high_pv_rate
                : (string) $rule->low_pv_rate;
            $pv = MlmDecimal::divide(
                MlmDecimal::multiply($amount, MlmDecimal::normalize($pvRate)),
                MlmDecimal::normalize((string) $rule->pv_divisor)
            );
            $rate = MlmDecimal::normalize((string) $rule->income_rate);
            $calculatedAmount = MlmDecimal::multiply($pv, $rate);

            $lines[] = [
                'member_id' => $beneficiary->id,
                'customer_id' => $beneficiary->customer_id,
                'member_name' => $beneficiary->user?->name,
                'level' => $level,
                'income_type' => $level === 0 ? 'own_purchase' : 'level_income',
                'pv_rate' => MlmDecimal::normalize($pvRate),
                'rate' => $rate,
                'pv' => $pv,
                'calculated_amount' => $calculatedAmount,
            ];
            $totalPv = MlmDecimal::add($totalPv, $pv);
            $totalIncome = MlmDecimal::add($totalIncome, $calculatedAmount);
        }

        return [
            'lines' => $lines,
            'total_pv' => $totalPv,
            'total_income' => $totalIncome,
            'placement_path' => $placementPath,
        ];
    }

    private function validateInputs(
        Member $purchasingMember,
        string $eligibleAmount,
        string $transactionReference,
        Carbon $date,
        MlmCalculationRule $rule
    ): string {
        if ($purchasingMember->status !== Member::STATUS_ACTIVE) {
            $this->fail('purchasing_member_id', 'The purchasing member must be active.');
        }

        try {
            $amount = MlmDecimal::normalize($eligibleAmount);
        } catch (\InvalidArgumentException) {
            $this->fail('eligible_amount', 'Eligible amount must be an exact non-negative decimal.');
        }

        if (! MlmDecimal::isPositive($amount)) {
            $this->fail('eligible_amount', 'Eligible amount must be greater than zero.');
        }
        if (trim($transactionReference) === '') {
            $this->fail('transaction_reference', 'Transaction reference is required.');
        }
        if (! $this->ruleWasEffective($rule, $date)) {
            $this->fail('rule_version_id', 'The selected calculation rule is not effective for this date.');
        }

        return $amount;
    }

    private function ruleWasEffective(MlmCalculationRule $rule, Carbon $date): bool
    {
        return (! $rule->effective_from || $date->toDateString() >= $rule->effective_from->toDateString())
            && (! $rule->effective_to || $date->toDateString() <= $rule->effective_to->toDateString());
    }

    private function placementPathAt(Member $member, Carbon $date): array
    {
        $futureMovement = PlacementMovement::query()
            ->where('member_id', $member->id)
            ->where('status', 'approved')
            ->where('effective_at', '>', $date)
            ->whereNotNull('old_path_snapshot')
            ->orderBy('effective_at')
            ->first();

        if ($futureMovement && is_array($futureMovement->old_path_snapshot)) {
            return array_map('intval', array_column($futureMovement->old_path_snapshot, 'id'));
        }

        $path = [];
        $visited = [];
        $current = $member;
        while ($current) {
            if (isset($visited[$current->id])) {
                $this->fail('purchasing_member_id', 'The placement path contains a circular relationship.');
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

        return $path;
    }

    private function idempotencyKey(Member $member, string $reference): string
    {
        return hash('sha256', $member->id.'|'.trim($reference));
    }

    private function fail(string $field, string $message): never
    {
        throw ValidationException::withMessages([$field => $message]);
    }
}
