<?php

namespace App\Console\Commands;

use Database\Seeders\MlmSampleDataSeeder;
use Illuminate\Console\Command;
use ZipArchive;

class ImportMlmExcelCommand extends Command
{
    protected $signature = 'mlm:import-excel {--file= : Custom path to the Excel file} {--seed : Re-seed the database immediately}';

    protected $description = 'Import client master data from Aaradhya Gruh Udyog Excel sheet into MLM backend';

    public function handle(): int
    {
        $filePath = $this->option('file') ?: base_path('excel sheetr/Aaradhya Gruh Udyog - Master Data.xlsx');

        if (! file_exists($filePath)) {
            $fallback = 'C:/Users/LENOVO/Downloads/Aaradhya Gruh Udyog - Master Data.xlsx';
            if (file_exists($fallback)) {
                $filePath = $fallback;
            } else {
                $this->error("Excel file not found at: {$filePath}");
                return self::FAILURE;
            }
        }

        $this->info("Opening master Excel file: {$filePath}");

        $zip = new ZipArchive();
        if ($zip->open($filePath, ZipArchive::RDONLY) !== true) {
            $this->error("Failed to open Excel archive at: {$filePath}");
            return self::FAILURE;
        }

        $this->info("Parsing shared strings and workbook relationships...");

        $strings = [];
        if ($zip->locateName('xl/sharedStrings.xml') !== false) {
            $xml = simplexml_load_string($zip->getFromName('xl/sharedStrings.xml'));
            foreach ($xml->si as $si) {
                if (isset($si->t)) {
                    $strings[] = (string) $si->t;
                } elseif (isset($si->r)) {
                    $t = '';
                    foreach ($si->r as $r) {
                        $t .= (string) $r->t;
                    }
                    $strings[] = $t;
                } else {
                    $strings[] = '';
                }
            }
        }

        $relsXml = simplexml_load_string($zip->getFromName('xl/_rels/workbook.xml.rels'));
        $sheetMap = [];
        foreach ($relsXml->Relationship as $rel) {
            $sheetMap[(string) $rel['Id']] = (string) $rel['Target'];
        }

        $wbXml = simplexml_load_string($zip->getFromName('xl/workbook.xml'));
        $sheetTargets = [];
        foreach ($wbXml->sheets->sheet as $sheet) {
            $sheetName = (string) $sheet['name'];
            $rId = (string) $sheet->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships')['id'];
            $sheetTargets[$sheetName] = 'xl/' . $sheetMap[$rId];
        }

        // Parse Customer sheet
        if (! isset($sheetTargets['Customer'])) {
            $this->error("Sheet 'Customer' not found in workbook!");
            $zip->close();
            return self::FAILURE;
        }

        $custXml = simplexml_load_string($zip->getFromName($sheetTargets['Customer']));
        $customers = [];
        foreach ($custXml->sheetData->row as $row) {
            $rNum = (int) $row['r'];
            if ($rNum < 3) continue;

            $cols = [];
            foreach ($row->c as $c) {
                $colLetter = preg_replace('/[0-9]/', '', (string) $c['r']);
                $t = (string) $c['t'];
                $val = '';
                if ($t === 's') {
                    $val = $strings[(int) $c->v] ?? '';
                } elseif (isset($c->v)) {
                    $val = (string) $c->v;
                }
                $cols[$colLetter] = trim($val);
            }

            $code = $cols['B'] ?? '';
            if (empty($code) || $code === 'C25070003') continue; // C25070003 was dummy M1; Nimesh C22020008 is Top Root M1

            $rawDate = $cols['C'] ?? '';
            $regDate = date('Y-m-d');
            if (is_numeric($rawDate)) {
                $regDate = date('Y-m-d', ($rawDate - 25569) * 86400);
            } elseif (! empty($rawDate) && ($t = strtotime($rawDate)) !== false) {
                $regDate = date('Y-m-d', $t);
            }

            $idleMap = [
                'C25070004' => 'Idle - 2',
                'C25070005' => 'Idle - 3',
                'C25070006' => 'Idle - 4',
                'C25070007' => 'Idle - 5',
                'C25070008' => 'Idle - 6',
                'C25070009' => 'Idle - 7',
                'C25070010' => 'Idle - 8',
                'C25070011' => 'Idle - 9',
                'C25070012' => 'Idle - 10',
                'C25070013' => 'Idle - 11',
                'C25070014' => 'Idle - 12',
                'C25070015' => 'Idle - 13',
            ];
            $name = $idleMap[$code] ?? ($cols['D'] ?? '');

            $customers[$code] = [
                'mobile' => $cols['A'] ?? '',
                'code' => $code,
                'reg_date' => $regDate,
                'name' => $name,
                'address' => $cols['E'] ?? '',
                'area' => $cols['F'] ?? '',
                'pincode' => $cols['G'] ?? '',
                'city' => $cols['H'] ?? 'Ahmedabad',
                'state' => $cols['I'] ?? 'Gujarat',
                'referral_mobile' => $cols['J'] ?? '',
                'referral_id' => $cols['K'] ?? '',
                'referral_name' => $cols['L'] ?? '',
                'status' => (strtolower(trim($cols['M'] ?? '')) === 'blocked') ? 'blocked' : 'active',
            ];
        }

        $this->info("Extracted " . count($customers) . " customer master records.");

        // Parse MLM Final sheet
        if (! isset($sheetTargets['MLM Final'])) {
            $this->error("Sheet 'MLM Final' not found in workbook!");
            $zip->close();
            return self::FAILURE;
        }

        $mlmXml = simplexml_load_string($zip->getFromName($sheetTargets['MLM Final']));
        $treePlacements = [];
        $uplineSlotCounters = [];

        $addPlacement = function (string $downline, string $upline) use (&$treePlacements, &$uplineSlotCounters): void {
            $slotIdx = ($uplineSlotCounters[$upline] ?? 0) + 1;
            $uplineSlotCounters[$upline] = $slotIdx;

            $position = match ($slotIdx) {
                1 => 'left',
                2 => 'middle',
                default => 'right',
            };

            $treePlacements[$downline] = [
                'upline' => $upline,
                'position' => $position,
                'slot_index' => $slotIdx,
            ];
        };

        // 1. Level 2: Immediate next 3 blank IDs under Top ID Nimesh (C22020008)
        $addPlacement('C25070004', 'C22020008'); // M2 (Idle - 2)
        $addPlacement('C25070005', 'C22020008'); // M3 (Idle - 3)
        $addPlacement('C25070006', 'C22020008'); // M4 (Idle - 4)

        // 2. Level 3: Following next 9 blank IDs under M2, M3, M4
        $addPlacement('C25070007', 'C25070004'); // M5 (Idle - 5)
        $addPlacement('C25070008', 'C25070004'); // M6 (Idle - 6)
        $addPlacement('C25070009', 'C25070004'); // M7 (Idle - 7)

        $addPlacement('C25070010', 'C25070005'); // M8 (Idle - 8)
        $addPlacement('C25070011', 'C25070005'); // M9 (Idle - 9)
        $addPlacement('C25070012', 'C25070005'); // M10 (Idle - 10)

        $addPlacement('C25070013', 'C25070006'); // M11 (Idle - 11)
        $addPlacement('C25070014', 'C25070006'); // M12 (Idle - 12)
        $addPlacement('C25070015', 'C25070006'); // M13 (Idle - 13)

        // 3. Level 4+: All members allotted below the 9 idle IDs according to MLM Final sheet
        foreach ($mlmXml->sheetData->row as $row) {
            $rNum = (int) $row['r'];
            if ($rNum < 15) continue; // Rows 3 to 14 were M1..M13 handled above

            $cols = [];
            foreach ($row->c as $c) {
                $colLetter = preg_replace('/[0-9]/', '', (string) $c['r']);
                $t = (string) $c['t'];
                $val = '';
                if ($t === 's') {
                    $val = $strings[(int) $c->v] ?? '';
                } elseif (isset($c->v)) {
                    $val = (string) $c->v;
                }
                $cols[$colLetter] = trim($val);
            }

            $upline = $cols['A'] ?? '';
            $downline = $cols['D'] ?? '';
            if (empty($upline) || empty($downline)) continue;

            // In Row 36, Nimesh was under M12. Since Nimesh is Root, place Harshendra Bais under M12
            if ($upline === 'C25070014' && $downline === 'C22020008') {
                $addPlacement('C22070047', 'C25070014');
                continue;
            }

            // In rows 579, 580, 581, upline was C22020008. Route Paresh and Dhara under Jayprakash Vyas
            if ($upline === 'C22020008') {
                if ($downline === 'C22070047') {
                    continue; // Placed under M12 above
                }
                if ($downline === 'C23010013' || $downline === 'C23010022') {
                    $addPlacement($downline, 'C22070021');
                    continue;
                }
            }

            if ($upline === 'C22110009' && $downline === 'C22080017') {
                continue;
            }

            $addPlacement($downline, $upline);
        }

        // Connect C22080017 under Jayesh Rana's branch (C22030010)
        if (isset($customers['C22080017']) && ! isset($treePlacements['C22080017'])) {
            $addPlacement('C22080017', 'C22030010');
        }

        // Connect C25120004 under Devang's branch (C22060010)
        if (isset($customers['C25120004']) && ! isset($treePlacements['C25120004'])) {
            $addPlacement('C25120004', 'C22060010');
        }

        $this->info("Extracted " . count($treePlacements) . " 1:3 matrix tree placements.");

        // Parse Transactions sheet
        $transactions = [];
        if (isset($sheetTargets['Transactions'])) {
            $txXml = simplexml_load_string($zip->getFromName($sheetTargets['Transactions']));
            foreach ($txXml->sheetData->row as $row) {
                $rNum = (int) $row['r'];
                if ($rNum < 3) continue;

                $cols = [];
                foreach ($row->c as $c) {
                    $colLetter = preg_replace('/[0-9]/', '', (string) $c['r']);
                    $t = (string) $c['t'];
                    $val = '';
                    if ($t === 's') {
                        $val = $strings[(int) $c->v] ?? '';
                    } elseif (isset($c->v)) {
                        $val = (string) $c->v;
                    }
                    $cols[$colLetter] = trim($val);
                }

                $txId = $cols['A'] ?? '';
                $custCode = $cols['D'] ?? '';
                $amount = (float) ($cols['I'] ?? 0);
                if (empty($txId) || empty($custCode) || $amount <= 0) continue;

                $rawDate = $cols['B'] ?? '';
                $txDate = date('Y-m-d');
                if (is_numeric($rawDate)) {
                    $txDate = date('Y-m-d', ($rawDate - 25569) * 86400);
                } elseif (! empty($rawDate) && ($t = strtotime($rawDate)) !== false) {
                    $txDate = date('Y-m-d', $t);
                }

                $transactions[] = [
                    'tx_id' => $txId,
                    'date' => $txDate,
                    'customer_code' => $custCode,
                    'particular' => $cols['F'] ?? '',
                    'qty' => (int) ($cols['G'] ?? 1),
                    'rate' => (float) ($cols['H'] ?? $amount),
                    'amount' => $amount,
                    'vendor_code' => $cols['N'] ?? 'V23080001',
                    'vendor' => $cols['O'] ?? 'Aaradhya Gruh Udyog',
                    'status' => 'paid',
                ];
            }
            $this->info("Extracted " . count($transactions) . " customer transaction records.");
        }

        $zip->close();

        // Save JSON data
        $targetJson = database_path('data/mlm_client_data.json');
        if (! is_dir(dirname($targetJson))) {
            mkdir(dirname($targetJson), 0755, true);
        }

        $exportData = [
            'root_code' => 'C22020008',
            'customers' => array_values($customers),
            'placements' => $treePlacements,
            'transactions' => $transactions,
        ];

        file_put_contents($targetJson, json_encode($exportData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        $this->info("Saved synchronized dataset to: {$targetJson}");

        // Automatically seed database
        $this->info("Re-seeding database with client master data...");
        $this->call(MlmSampleDataSeeder::class);

        $this->info("Successfully imported client master data and updated MLM database!");
        return self::SUCCESS;
    }
}
