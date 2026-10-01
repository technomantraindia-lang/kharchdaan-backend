<?php

namespace App\Services\Mlm;

use App\Models\MlmMemberSequence;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class MlmMemberIdGenerator
{
    public function next(?CarbonInterface $date = null): string
    {
        $date ??= now();
        $period = $date->format('ym');

        for ($attempt = 1; $attempt <= 3; $attempt++) {
            try {
                return DB::transaction(function () use ($period): string {
                    $sequence = MlmMemberSequence::query()
                        ->where('period', $period)
                        ->lockForUpdate()
                        ->first();

                    if (! $sequence) {
                        $nextNumber = $this->nextNumberAfterExistingIds($period);
                        $sequence = MlmMemberSequence::create([
                            'period' => $period,
                            'next_number' => $nextNumber,
                        ]);
                    }

                    $number = $sequence->next_number;

                    if ($number > 9999) {
                        throw new RuntimeException("The MLM member sequence for {$period} is exhausted.");
                    }

                    $sequence->update(['next_number' => $number + 1]);

                    return 'C'.$period.str_pad((string) $number, 4, '0', STR_PAD_LEFT);
                });
            } catch (QueryException $exception) {
                if ($attempt === 3) {
                    throw $exception;
                }

                usleep(10000 * $attempt);
            }
        }

        throw new RuntimeException('Unable to allocate an MLM member ID.');
    }

    private function nextNumberAfterExistingIds(string $period): int
    {
        $prefix = 'C'.$period;
        $max = 0;

        User::query()
            ->where('mlm_member_id', 'like', $prefix.'%')
            ->pluck('mlm_member_id')
            ->each(function (?string $memberId) use (&$max, $prefix): void {
                $suffix = substr((string) $memberId, strlen($prefix));
                if (ctype_digit($suffix)) {
                    $max = max($max, (int) $suffix);
                }
            });

        return $max + 1;
    }
}
