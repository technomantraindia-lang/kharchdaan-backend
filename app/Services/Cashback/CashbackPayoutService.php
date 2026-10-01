<?php

namespace App\Services\Cashback;

use App\Models\CashbackActionHistory;
use App\Models\CashbackEligibility;
use App\Models\CashbackPayoutBatch;
use App\Models\CashbackPayoutBatchItem;
use App\Models\CashbackProfitPool;
use App\Models\CashbackStatusHistory;
use App\Models\User;
use App\Notifications\SystemAlertNotification;
use App\Support\MlmDecimal;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CashbackPayoutService
{
    public const ACTIVE_BATCH_STATUSES = [
        CashbackPayoutBatch::STATUS_PENDING_APPROVAL,
        CashbackPayoutBatch::STATUS_APPROVED,
        CashbackPayoutBatch::STATUS_SCHEDULED,
        CashbackPayoutBatch::STATUS_PROCESSING,
    ];

    public function eligibleQuery(array $filters = []): Builder
    {
        return CashbackEligibility::query()
            ->with(['member.user', 'order', 'selectedPool', 'payoutBatchItems.batch'])
            ->when(! empty($filters['status']), function (Builder $query) use ($filters): void {
                $query->where('status', $filters['status']);
            }, function (Builder $query): void {
                $query->whereIn('status', [
                    CashbackEligibility::STATUS_ELIGIBLE_AWAITING_COMPANY_PROFIT,
                    CashbackEligibility::STATUS_SELECTED_BY_ADMIN,
                    CashbackEligibility::STATUS_APPROVED,
                    CashbackEligibility::STATUS_SCHEDULED,
                    CashbackEligibility::STATUS_PROCESSING,
                    CashbackEligibility::STATUS_PAID,
                    CashbackEligibility::STATUS_ON_HOLD,
                    CashbackEligibility::STATUS_REVERSED,
                    CashbackEligibility::STATUS_CANCELLED_DUE_TO_REFUND,
                ]);
            })
            ->when(! empty($filters['date_from']), fn (Builder $query) => $query->whereDate('eligibility_date', '>=', $filters['date_from']))
            ->when(! empty($filters['date_to']), fn (Builder $query) => $query->whereDate('eligibility_date', '<=', $filters['date_to']))
            ->when(! empty($filters['member_id']), fn (Builder $query) => $query->where('member_id', (int) $filters['member_id']))
            ->when(! empty($filters['order_num']), fn (Builder $query) => $query->whereHas('order', fn (Builder $order) => $order->where('order_num', 'like', '%'.trim($filters['order_num']).'%')))
            ->when(($filters['amount_min'] ?? '') !== '', fn (Builder $query) => $query->where('maximum_cashback_amount', '>=', MlmDecimal::normalize((string) $filters['amount_min'])))
            ->when(($filters['amount_max'] ?? '') !== '', fn (Builder $query) => $query->where('maximum_cashback_amount', '<=', MlmDecimal::normalize((string) $filters['amount_max'])));
    }

    public function declarePool(string $amount, string $reference, ?string $note, User $admin): CashbackProfitPool
    {
        try {
            $normalized = MlmDecimal::normalize($amount);
        } catch (\InvalidArgumentException) {
            $this->fail('amount', 'The approved pool amount must be an exact non-negative decimal.');
        }

        if (! MlmDecimal::isPositive($normalized)) {
            $this->fail('amount', 'The approved pool amount must be greater than zero.');
        }
        if (trim($reference) === '') {
            $this->fail('pool_reference', 'A pool reference is required.');
        }

        return DB::transaction(function () use ($normalized, $reference, $note, $admin): CashbackProfitPool {
            return CashbackProfitPool::create([
                'pool_reference' => trim($reference),
                'approved_available_amount' => $normalized,
                'allocated_amount' => MlmDecimal::normalize('0'),
                'status' => CashbackProfitPool::STATUS_ACTIVE,
                'approved_by' => $admin->id,
                'approved_at' => now(),
                'admin_note' => $note ? trim($note) : null,
            ]);
        });
    }

    public function selectRecords(
        CashbackProfitPool $pool,
        array $filters,
        array $cashbackIds,
        bool $selectAll,
        User $admin
    ): int {
        return DB::transaction(function () use ($pool, $filters, $cashbackIds, $selectAll, $admin): int {
            $lockedPool = CashbackProfitPool::query()->lockForUpdate()->findOrFail($pool->id);
            if ($lockedPool->status !== CashbackProfitPool::STATUS_ACTIVE) {
                $this->fail('pool_id', 'The selected profit pool is not active.');
            }

            $query = $this->eligibleQuery($filters)
                ->where('status', CashbackEligibility::STATUS_ELIGIBLE_AWAITING_COMPANY_PROFIT)
                ->lockForUpdate();
            if (! $selectAll) {
                $query->whereIn('id', array_map('intval', $cashbackIds));
            }

            $records = $query->get();
            if ($records->isEmpty()) {
                $this->fail('cashback_ids', 'Select at least one eligible cashback record.');
            }

            if ($records->contains(fn (CashbackEligibility $record): bool => $record->selected_pool_id !== null)) {
                $this->fail('cashback_ids', 'One or more records are already selected for another pool.');
            }

            $selectedTotal = $this->selectedTotal($lockedPool->id);
            $newTotal = $this->sumAmounts($records->pluck('maximum_cashback_amount')->all());
            $combined = MlmDecimal::add($selectedTotal, $newTotal);
            $available = MlmDecimal::subtract(
                (string) $lockedPool->approved_available_amount,
                (string) $lockedPool->allocated_amount
            );
            if (bccomp($combined, $available, MlmDecimal::SCALE) > 0) {
                $this->fail('cashback_ids', 'The selected cashback total exceeds the remaining approved pool.');
            }

            foreach ($records as $record) {
                $from = $record->status;
                $record->update([
                    'status' => CashbackEligibility::STATUS_SELECTED_BY_ADMIN,
                    'selected_pool_id' => $lockedPool->id,
                    'selected_by' => $admin->id,
                    'selected_at' => now(),
                ]);
                $this->recordStatus($record, $from, CashbackEligibility::STATUS_SELECTED_BY_ADMIN, $admin, null, 'Selected for cashback pool.');
                $this->recordAction($record, null, 'selected', 'Selected for cashback pool.', $admin);
            }

            return $records->count();
        });
    }

    public function createBatch(CashbackProfitPool $pool, User $admin): CashbackPayoutBatch
    {
        return DB::transaction(function () use ($pool, $admin): CashbackPayoutBatch {
            $lockedPool = CashbackProfitPool::query()->lockForUpdate()->findOrFail($pool->id);
            $records = CashbackEligibility::query()
                ->where('selected_pool_id', $lockedPool->id)
                ->where('status', CashbackEligibility::STATUS_SELECTED_BY_ADMIN)
                ->whereDoesntHave('payoutBatchItems')
                ->lockForUpdate()
                ->get();
            if ($records->isEmpty()) {
                $this->fail('pool_id', 'There are no selected records available for a payout batch.');
            }

            $total = $this->sumAmounts($records->pluck('maximum_cashback_amount')->all());
            $available = MlmDecimal::subtract(
                (string) $lockedPool->approved_available_amount,
                (string) $lockedPool->allocated_amount
            );
            if (bccomp($total, $available, MlmDecimal::SCALE) > 0) {
                $this->fail('pool_id', 'The selected total exceeds the remaining approved pool.');
            }

            $batch = CashbackPayoutBatch::create([
                'pool_id' => $lockedPool->id,
                'batch_reference' => 'CB-BATCH-'.strtoupper(bin2hex(random_bytes(5))),
                'total_amount' => $total,
                'record_count' => $records->count(),
                'status' => CashbackPayoutBatch::STATUS_PENDING_APPROVAL,
                'created_by' => $admin->id,
            ]);

            foreach ($records as $record) {
                CashbackPayoutBatchItem::create([
                    'batch_id' => $batch->id,
                    'cashback_eligibility_id' => $record->id,
                    'order_id' => $record->order_id,
                    'member_id' => $record->member_id,
                    'amount' => $record->maximum_cashback_amount,
                    'record_snapshot' => [
                        'order_id' => $record->order_id,
                        'member_id' => $record->member_id,
                        'maximum_cashback_amount' => (string) $record->maximum_cashback_amount,
                        'eligibility_date' => $record->eligibility_date?->toDateTimeString(),
                    ],
                ]);
                $this->recordAction($record, $batch, 'batch_created', 'Included in cashback payout batch.', $admin);
            }

            $lockedPool->update([
                'allocated_amount' => MlmDecimal::add((string) $lockedPool->allocated_amount, $total),
            ]);
            $this->recordAction(null, $batch, 'batch_created', 'Cashback payout batch created.', $admin);

            return $batch->fresh(['pool', 'items.cashbackEligibility']);
        });
    }

    public function approveBatch(CashbackPayoutBatch $batch, User $admin): CashbackPayoutBatch
    {
        return $this->transitionBatch($batch, CashbackPayoutBatch::STATUS_PENDING_APPROVAL, CashbackPayoutBatch::STATUS_APPROVED, $admin, [
            'approved_by' => $admin->id,
            'approved_at' => now(),
        ]);
    }

    public function scheduleBatch(CashbackPayoutBatch $batch, Carbon|string $date, User $admin): CashbackPayoutBatch
    {
        $scheduledDate = Carbon::parse($date);

        return $this->transitionBatch($batch, CashbackPayoutBatch::STATUS_APPROVED, CashbackPayoutBatch::STATUS_SCHEDULED, $admin, [
            'scheduled_payment_date' => $scheduledDate,
            'scheduled_by' => $admin->id,
            'scheduled_at' => now(),
        ]);
    }

    public function startProcessing(CashbackPayoutBatch $batch, User $admin): CashbackPayoutBatch
    {
        return $this->transitionBatch($batch, CashbackPayoutBatch::STATUS_SCHEDULED, CashbackPayoutBatch::STATUS_PROCESSING, $admin, [
            'processed_by' => $admin->id,
            'processed_at' => now(),
        ]);
    }

    public function markPaid(
        CashbackPayoutBatch $batch,
        string $paymentReference,
        UploadedFile $paymentProof,
        User $admin,
        ?string $note = null
    ): CashbackPayoutBatch {
        if (trim($paymentReference) === '') {
            $this->fail('payment_reference', 'Payment reference is required before marking paid.');
        }

        return DB::transaction(function () use ($batch, $paymentReference, $paymentProof, $admin, $note): CashbackPayoutBatch {
            $lockedBatch = CashbackPayoutBatch::query()->lockForUpdate()->findOrFail($batch->id);
            $this->assertBatchStatus($lockedBatch, CashbackPayoutBatch::STATUS_PROCESSING);
            $items = $lockedBatch->items()->lockForUpdate()->get();
            $cashbacks = CashbackEligibility::query()->whereIn('id', $items->pluck('cashback_eligibility_id'))->lockForUpdate()->get()->keyBy('id');
            if ($cashbacks->count() !== $items->count() || $cashbacks->contains(fn (CashbackEligibility $cashback): bool => $cashback->status !== CashbackEligibility::STATUS_PROCESSING)) {
                $this->fail('batch', 'Every cashback record must be processing before payment.');
            }

            $proofPath = $paymentProof->store('cashback/payment-proofs', 'local');
            $lockedBatch->update([
                'status' => CashbackPayoutBatch::STATUS_PAID,
                'payment_reference' => trim($paymentReference),
                'payment_proof_path' => $proofPath,
                'admin_note' => $note !== null ? trim($note) : $lockedBatch->admin_note,
                'paid_by' => $admin->id,
                'paid_at' => now(),
            ]);

            foreach ($cashbacks as $cashback) {
                $from = $cashback->status;
                $cashback->update(['status' => CashbackEligibility::STATUS_PAID]);
                $this->recordStatus($cashback, $from, CashbackEligibility::STATUS_PAID, $admin, $lockedBatch, 'Manual cashback payment recorded.');
                $this->recordAction($cashback, $lockedBatch, 'paid', 'Manual cashback payment recorded.', $admin, ['payment_reference' => trim($paymentReference)]);
            }
            $this->recordAction(null, $lockedBatch, 'paid', 'Cashback payout batch marked paid.', $admin, ['payment_reference' => trim($paymentReference)]);

            return $lockedBatch->fresh(['pool', 'items.cashbackEligibility']);
        });
    }

    public function hold(CashbackEligibility $cashback, string $reason, User $admin): CashbackEligibility
    {
        if (trim($reason) === '') {
            $this->fail('reason', 'A hold reason is required.');
        }

        return DB::transaction(function () use ($cashback, $reason, $admin): CashbackEligibility {
            $locked = CashbackEligibility::query()->lockForUpdate()->findOrFail($cashback->id);
            if (! in_array($locked->status, [CashbackEligibility::STATUS_ELIGIBLE_AWAITING_COMPANY_PROFIT, CashbackEligibility::STATUS_SELECTED_BY_ADMIN], true)) {
                $this->fail('cashback', 'Only unbatched eligible or selected records can be put on hold.');
            }
            if ($locked->status === CashbackEligibility::STATUS_SELECTED_BY_ADMIN) {
                $this->releaseBatchItem($locked);
            }
            $from = $locked->status;
            $locked->update([
                'status' => CashbackEligibility::STATUS_ON_HOLD,
                'ineligibility_reason' => trim($reason),
                'selected_pool_id' => null,
                'selected_by' => null,
                'selected_at' => null,
            ]);
            $this->recordStatus($locked, $from, CashbackEligibility::STATUS_ON_HOLD, $admin, null, $reason);
            $this->recordAction($locked, null, 'hold', $reason, $admin);

            return $locked->fresh(['order', 'member', 'adjustments']);
        });
    }

    public function reverse(CashbackEligibility $cashback, string $reason, User $admin): CashbackEligibility
    {
        if (trim($reason) === '') {
            $this->fail('reason', 'A reversal reason is required.');
        }

        return DB::transaction(function () use ($cashback, $reason, $admin): CashbackEligibility {
            $locked = CashbackEligibility::query()->lockForUpdate()->findOrFail($cashback->id);
            if (! in_array($locked->status, [
                CashbackEligibility::STATUS_SELECTED_BY_ADMIN,
                CashbackEligibility::STATUS_APPROVED,
                CashbackEligibility::STATUS_SCHEDULED,
                CashbackEligibility::STATUS_PROCESSING,
                CashbackEligibility::STATUS_PAID,
                CashbackEligibility::STATUS_ON_HOLD,
            ], true)) {
                $this->fail('cashback', 'This cashback record cannot be reversed from its current status.');
            }

            if ($locked->status !== CashbackEligibility::STATUS_PAID) {
                $this->releaseBatchItem($locked);
            }
            app(CashbackEligibilityService::class)->createReversalAdjustment($locked, $reason, $admin);
            $from = $locked->status;
            $locked->update([
                'status' => CashbackEligibility::STATUS_REVERSED,
                'ineligibility_reason' => trim($reason),
            ]);
            $this->recordStatus($locked, $from, CashbackEligibility::STATUS_REVERSED, $admin, null, $reason);
            $this->recordAction($locked, null, 'reversed', $reason, $admin);

            return $locked->fresh(['order', 'member', 'adjustments']);
        });
    }

    public function releaseForCancellation(CashbackEligibility $cashback): void
    {
        if ($cashback->status !== CashbackEligibility::STATUS_PAID) {
            $this->releaseBatchItem($cashback);
        }
    }

    public function poolSummary(?CashbackProfitPool $pool): array
    {
        if (! $pool) {
            return [
                'approved' => MlmDecimal::normalize('0'),
                'allocated' => MlmDecimal::normalize('0'),
                'available' => MlmDecimal::normalize('0'),
                'selected' => MlmDecimal::normalize('0'),
                'remaining' => MlmDecimal::normalize('0'),
                'selected_count' => 0,
            ];
        }

        $selected = CashbackEligibility::query()
            ->where('selected_pool_id', $pool->id)
            ->where('status', CashbackEligibility::STATUS_SELECTED_BY_ADMIN)
            ->whereDoesntHave('payoutBatchItems')
            ->get();
        $selectedTotal = $this->sumAmounts($selected->pluck('maximum_cashback_amount')->all());
        $available = MlmDecimal::subtract((string) $pool->approved_available_amount, (string) $pool->allocated_amount);

        return [
            'approved' => (string) $pool->approved_available_amount,
            'allocated' => (string) $pool->allocated_amount,
            'available' => $available,
            'selected' => $selectedTotal,
            'remaining' => MlmDecimal::subtract($available, $selectedTotal),
            'selected_count' => $selected->count(),
        ];
    }

    private function transitionBatch(CashbackPayoutBatch $batch, string $fromStatus, string $toStatus, User $admin, array $attributes): CashbackPayoutBatch
    {
        return DB::transaction(function () use ($batch, $fromStatus, $toStatus, $admin, $attributes): CashbackPayoutBatch {
            $lockedBatch = CashbackPayoutBatch::query()->lockForUpdate()->findOrFail($batch->id);
            $this->assertBatchStatus($lockedBatch, $fromStatus);
            $items = $lockedBatch->items()->lockForUpdate()->get();
            $cashbacks = CashbackEligibility::query()->whereIn('id', $items->pluck('cashback_eligibility_id'))->lockForUpdate()->get()->keyBy('id');
            $expectedRecordStatus = match ($toStatus) {
                CashbackPayoutBatch::STATUS_APPROVED => CashbackEligibility::STATUS_SELECTED_BY_ADMIN,
                CashbackPayoutBatch::STATUS_SCHEDULED => CashbackEligibility::STATUS_APPROVED,
                CashbackPayoutBatch::STATUS_PROCESSING => CashbackEligibility::STATUS_SCHEDULED,
                default => null,
            };
            $newRecordStatus = match ($toStatus) {
                CashbackPayoutBatch::STATUS_APPROVED => CashbackEligibility::STATUS_APPROVED,
                CashbackPayoutBatch::STATUS_SCHEDULED => CashbackEligibility::STATUS_SCHEDULED,
                CashbackPayoutBatch::STATUS_PROCESSING => CashbackEligibility::STATUS_PROCESSING,
                default => null,
            };
            if ($expectedRecordStatus && $cashbacks->contains(fn (CashbackEligibility $cashback): bool => $cashback->status !== $expectedRecordStatus)) {
                $this->fail('batch', 'One or more cashback records are no longer available for this batch.');
            }

            $lockedBatch->update(['status' => $toStatus, ...$attributes]);
            if ($newRecordStatus) {
                foreach ($cashbacks as $cashback) {
                    $from = $cashback->status;
                    $cashback->update(['status' => $newRecordStatus]);
                    $this->recordStatus($cashback, $from, $newRecordStatus, $admin, $lockedBatch, 'Cashback batch status changed.');
                    $this->recordAction($cashback, $lockedBatch, strtolower($toStatus), 'Cashback batch status changed.', $admin);
                }
            }
            $this->recordAction(null, $lockedBatch, strtolower($toStatus), 'Cashback payout batch status changed.', $admin);

            return $lockedBatch->fresh(['pool', 'items.cashbackEligibility']);
        });
    }

    private function selectedTotal(int $poolId): string
    {
        return $this->sumAmounts(CashbackEligibility::query()
            ->where('selected_pool_id', $poolId)
            ->where('status', CashbackEligibility::STATUS_SELECTED_BY_ADMIN)
            ->whereDoesntHave('payoutBatchItems')
            ->lockForUpdate()
            ->get()
            ->pluck('maximum_cashback_amount')
            ->all());
    }

    private function sumAmounts(array $amounts): string
    {
        return collect($amounts)->reduce(
            fn (string $total, $amount): string => MlmDecimal::add($total, MlmDecimal::normalize((string) $amount)),
            MlmDecimal::normalize('0')
        );
    }

    private function releaseBatchItem(CashbackEligibility $cashback): void
    {
        $item = CashbackPayoutBatchItem::query()
            ->where('cashback_eligibility_id', $cashback->id)
            ->lockForUpdate()
            ->first();
        if (! $item) {
            return;
        }

        $batch = $item->batch()->lockForUpdate()->first();
        if (! $batch || in_array($batch->status, [CashbackPayoutBatch::STATUS_PAID, CashbackPayoutBatch::STATUS_PROCESSING], true)) {
            $this->fail('cashback', 'This cashback cannot be changed while its payout batch is processing or paid.');
        }
        $amount = MlmDecimal::normalize((string) $item->amount);
        $newBatchTotal = MlmDecimal::subtract((string) $batch->total_amount, $amount);
        if (bccomp($newBatchTotal, '0', MlmDecimal::SCALE) < 0) {
            $this->fail('cashback', 'Cashback batch total cannot become negative.');
        }
        $pool = $batch->pool()->lockForUpdate()->first();
        if (! $pool) {
            $this->fail('cashback', 'Cashback profit pool is missing.');
        }
        $newAllocation = MlmDecimal::subtract((string) $pool->allocated_amount, $amount);
        if (bccomp($newAllocation, '0', MlmDecimal::SCALE) < 0) {
            $this->fail('cashback', 'Cashback profit-pool allocation cannot become negative.');
        }
        $batch->update([
            'total_amount' => $newBatchTotal,
            'record_count' => max(0, (int) $batch->record_count - 1),
        ]);
        $pool->update(['allocated_amount' => $newAllocation]);
        $item->delete();
    }

    private function recordStatus(
        CashbackEligibility $cashback,
        ?string $from,
        string $to,
        User $admin,
        ?CashbackPayoutBatch $batch,
        ?string $reason = null
    ): void {
        CashbackStatusHistory::create([
            'cashback_eligibility_id' => $cashback->id,
            'batch_id' => $batch?->id,
            'from_status' => $from,
            'to_status' => $to,
            'reason' => $reason,
            'acted_by' => $admin->id,
        ]);

        $cashback->loadMissing('member.user');
        $message = match ($to) {
            CashbackEligibility::STATUS_SELECTED_BY_ADMIN => 'Your cashback was selected by the company and remains subject to approval.',
            CashbackEligibility::STATUS_APPROVED => 'Your cashback was approved by the company.',
            CashbackEligibility::STATUS_SCHEDULED => 'Your cashback has been scheduled by the company.',
            CashbackEligibility::STATUS_PROCESSING => 'Your cashback payment is being processed.',
            CashbackEligibility::STATUS_PAID => 'Your cashback payment has been marked paid.',
            CashbackEligibility::STATUS_ON_HOLD => 'Your cashback has been placed on hold.',
            CashbackEligibility::STATUS_REVERSED => 'Your cashback was reversed.',
            default => null,
        };
        if ($message) {
            $cashback->member?->user?->notify(new SystemAlertNotification(
                'Cashback update', $message, 'info', route('cashback.show', $cashback),
                ['cashback_id' => $cashback->id, 'status' => $to]
            ));
        }
    }

    private function recordAction(
        ?CashbackEligibility $cashback,
        ?CashbackPayoutBatch $batch,
        string $action,
        ?string $reason,
        User $admin,
        array $metadata = []
    ): void {
        CashbackActionHistory::create([
            'cashback_eligibility_id' => $cashback?->id,
            'batch_id' => $batch?->id,
            'action' => $action,
            'reason' => $reason,
            'acted_by' => $admin->id,
            'metadata' => $metadata ?: null,
        ]);
    }

    private function assertBatchStatus(CashbackPayoutBatch $batch, string $expected): void
    {
        if ($batch->status !== $expected) {
            $this->fail('batch', 'The cashback payout batch cannot transition from its current status.');
        }
    }

    private function fail(string $field, string $message): never
    {
        throw ValidationException::withMessages([$field => $message]);
    }
}
