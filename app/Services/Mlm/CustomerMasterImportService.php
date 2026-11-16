<?php

namespace App\Services\Mlm;

use App\Models\Member;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;

class CustomerMasterImportService
{
    /**
     * Non-destructive CSV import. Expected headers (case-insensitive):
     * code, name, mobile, email, sponsor_code, status
     *
     * @return array{created:int, updated:int, skipped:int, errors:array<int, string>}
     */
    public function importCsv(UploadedFile $file): array
    {
        $handle = fopen($file->getRealPath(), 'r');
        if ($handle === false) {
            throw new RuntimeException('Unable to read uploaded CSV.');
        }

        $header = fgetcsv($handle);
        if (! $header) {
            fclose($handle);
            throw new RuntimeException('CSV is empty.');
        }

        $map = [];
        foreach ($header as $i => $col) {
            $key = Str::of((string) $col)->lower()->replace([' ', '-'], '_')->toString();
            $map[$key] = $i;
        }

        $required = ['code', 'name'];
        foreach ($required as $col) {
            if (! array_key_exists($col, $map) && ! ($col === 'code' && array_key_exists('customer_id', $map))) {
                fclose($handle);
                throw new RuntimeException("CSV missing required column: {$col}");
            }
        }

        $codeIdx = $map['code'] ?? $map['customer_id'] ?? null;
        $nameIdx = $map['name'];
        $mobileIdx = $map['mobile'] ?? $map['phone'] ?? null;
        $emailIdx = $map['email'] ?? null;
        $sponsorIdx = $map['sponsor_code'] ?? $map['referral_id'] ?? $map['sponsor'] ?? null;
        $statusIdx = $map['status'] ?? null;

        $customerRole = Role::firstOrCreate(['name' => 'Customer'], ['guard_name' => 'web']);
        $created = 0;
        $updated = 0;
        $skipped = 0;
        $errors = [];
        $rowNum = 1;

        DB::beginTransaction();
        try {
            while (($row = fgetcsv($handle)) !== false) {
                $rowNum++;
                if (! is_array($row) || count(array_filter($row, fn ($v) => trim((string) $v) !== '')) === 0) {
                    continue;
                }

                $code = trim((string) ($row[$codeIdx] ?? ''));
                $name = trim((string) ($row[$nameIdx] ?? ''));
                if ($code === '' || $name === '') {
                    $skipped++;
                    $errors[] = "Row {$rowNum}: missing code or name.";
                    continue;
                }

                $mobile = $mobileIdx !== null ? preg_replace('/\D+/', '', (string) ($row[$mobileIdx] ?? '')) : '';
                $email = $emailIdx !== null ? strtolower(trim((string) ($row[$emailIdx] ?? ''))) : '';
                if ($email === '') {
                    $email = strtolower($code).'@kharchdaan.import';
                }
                $sponsorCode = $sponsorIdx !== null ? trim((string) ($row[$sponsorIdx] ?? '')) : '';
                $status = $statusIdx !== null ? strtolower(trim((string) ($row[$statusIdx] ?? 'active'))) : 'active';
                if (! in_array($status, ['active', 'blocked', 'inactive'], true)) {
                    $status = 'active';
                }

                $user = User::query()->where('mlm_member_id', $code)->first();
                if (! $user && $mobile !== '') {
                    $user = User::query()->where('phone', $mobile)->first();
                }

                $sponsorMemberId = null;
                if ($sponsorCode !== '') {
                    $sponsorMemberId = Member::query()
                        ->whereHas('user', fn ($q) => $q->where('mlm_member_id', $sponsorCode))
                        ->value('id');
                }

                if ($user) {
                    $user->fill([
                        'name' => $name,
                        'email' => $user->email ?: $email,
                        'phone' => $mobile !== '' ? $mobile : $user->phone,
                        'mlm_member_id' => $code,
                        'status' => $status === 'blocked' ? 'blocked' : 'active',
                        'role_id' => $customerRole->id,
                    ])->save();

                    $member = Member::query()->firstOrCreate(
                        ['user_id' => $user->id],
                        [
                            'status' => $status === 'blocked' ? Member::STATUS_BLOCKED : Member::STATUS_ACTIVE,
                            'joined_at' => now(),
                            'kyc_status' => Member::KYC_PENDING,
                        ]
                    );

                    if ($sponsorMemberId && ! $member->sponsor_member_id) {
                        $member->update(['sponsor_member_id' => $sponsorMemberId]);
                    }

                    $updated++;
                } else {
                    if ($mobile !== '' && User::query()->where('phone', $mobile)->exists()) {
                        $skipped++;
                        $errors[] = "Row {$rowNum}: phone {$mobile} already used.";
                        continue;
                    }
                    if (User::query()->where('email', $email)->exists()) {
                        $email = strtolower($code).'+'.Str::random(4).'@kharchdaan.import';
                    }

                    $user = User::create([
                        'name' => $name,
                        'email' => $email,
                        'password' => Hash::make('password'),
                        'plain_password' => 'password',
                        'phone' => $mobile !== '' ? $mobile : null,
                        'role_id' => $customerRole->id,
                        'status' => $status === 'blocked' ? 'blocked' : 'active',
                        'mlm_member_id' => $code,
                    ]);

                    Member::create([
                        'user_id' => $user->id,
                        'sponsor_member_id' => $sponsorMemberId,
                        'status' => $status === 'blocked' ? Member::STATUS_BLOCKED : Member::STATUS_ACTIVE,
                        'joined_at' => now(),
                        'kyc_status' => Member::KYC_PENDING,
                    ]);
                    $created++;
                }
            }
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            fclose($handle);
            throw $e;
        }

        fclose($handle);

        return compact('created', 'updated', 'skipped', 'errors');
    }
}
