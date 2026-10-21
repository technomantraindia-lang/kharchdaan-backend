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
            if (empty($code)) continue;

            $rawDate = $cols['C'] ?? '';
            $regDate = date('Y-m-d');
            if (is_numeric($rawDate)) {
                $regDate = date('Y-m-d', ($rawDate - 25569) * 86400);
            } elseif (! empty($rawDate) && ($t = strtotime($rawDate)) !== false) {
                $regDate = date('Y-m-d', $t);
            }

            $customers[$code] = [
                'mobile' => $cols['A'] ?? '',
                'code' => $code,
                'reg_date' => $regDate,
                'name' => $cols['D'] ?? '',
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

        foreach ($mlmXml->sheetData->row as $row) {
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

            $upline = $cols['A'] ?? '';
            $downline = $cols['D'] ?? '';
            if (empty($upline) || empty($downline)) continue;

            if ($upline === 'C22110009' && $downline === 'C22080017') {
                continue;
            }

            $slotIdx = ($uplineSlotCounters[$upline] ?? 0) + 1;
            $uplineSlotCounters[$upline] = $slotIdx;

            $position = match ($slotIdx) {
                1 => 'left',
                2 => 'middle',
                3 => 'right',
                default => 'right',
            };

            $treePlacements[$downline] = [
                'upline' => $upline,
                'position' => $position,
                'slot_index' => $slotIdx,
            ];
        }

        // Connect C22080017 under Jayesh Rana's branch (C22030010)
        if (isset($customers['C22080017']) && ! isset($treePlacements['C22080017'])) {
            $treePlacements['C22080017'] = [
                'upline' => 'C22030010',
                'position' => 'left',
                'slot_index' => 1,
            ];
        }

        // Connect C25120004 under Devang's branch (C22060010)
        if (isset($customers['C25120004']) && ! isset($treePlacements['C25120004'])) {
            $treePlacements['C25120004'] = [
                'upline' => 'C22060010',
                'position' => 'left',
                'slot_index' => 1,
            ];
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
            'root_code' => 'C25070003',
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
