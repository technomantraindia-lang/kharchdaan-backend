<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class BackupController extends Controller
{
    private function checkSuperAdmin(Request $request): void
    {
        $user = auth()->user();
        if (! $user || ! $user->isSuperAdmin()) {
            if ($request->wantsJson() || $request->ajax()) {
                abort(response()->json(['success' => false, 'message' => 'Super Admin access required.'], 403));
            }
            abort(403, 'Super Admin access required.');
        }
    }

    public function index(Request $request)
    {
        $this->checkSuperAdmin($request);

        $backupDir = storage_path('app/backups');
        if (! File::exists($backupDir)) {
            File::makeDirectory($backupDir, 0755, true);
        }

        $files = File::files($backupDir);
        $backups = [];

        foreach ($files as $file) {
            $backups[] = [
                'filename' => $file->getFilename(),
                'size' => round($file->getSize() / 1024 / 1024, 2) . ' MB',
                'created_at' => date('Y-m-d H:i:s', $file->getMTime()),
                'path' => $file->getRealPath(),
            ];
        }

        usort($backups, fn ($a, $b) => strcmp($b['created_at'], $a['created_at']));

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'backups' => $backups,
            ]);
        }

        return view('admin.system.backups', [
            'backups' => $backups,
        ]);
    }

    public function create(Request $request)
    {
        $this->checkSuperAdmin($request);

        $backupDir = storage_path('app/backups');
        if (! File::exists($backupDir)) {
            File::makeDirectory($backupDir, 0755, true);
        }

        $timestamp = date('Y-m-d_His');
        $filename = "backup_db_app_{$timestamp}.zip";
        $zipPath = $backupDir . DIRECTORY_SEPARATOR . $filename;

        $driver = config('database.default');
        $tempSqlFile = null;
        $dumpSuccess = false;
        $dumpErrorMessage = null;

        if ($driver === 'mysql') {
            $host = config('database.connections.mysql.host', '127.0.0.1');
            $port = config('database.connections.mysql.port', '3306');
            $database = config('database.connections.mysql.database');
            $username = config('database.connections.mysql.username');
            $password = config('database.connections.mysql.password');

            $tempSqlFile = storage_path('app/backups/temp_' . $timestamp . '.sql');

            try {
                $cmd = [
                    'mysqldump',
                    "--host={$host}",
                    "--port={$port}",
                    "--user={$username}",
                ];
                if (! empty($password)) {
                    $cmd[] = "--password={$password}";
                }
                $cmd[] = $database;

                $processResult = \Illuminate\Support\Facades\Process::run(implode(' ', array_map('escapeshellarg', $cmd)) . ' > ' . escapeshellarg($tempSqlFile));

                if ($processResult->successful() && File::exists($tempSqlFile) && File::size($tempSqlFile) > 0) {
                    $dumpSuccess = true;
                } else {
                    $dumpErrorMessage = 'mysqldump command failed or returned empty output. ' . $processResult->errorOutput();
                }
            } catch (\Throwable $e) {
                $dumpErrorMessage = 'mysqldump execution error: ' . $e->getMessage();
            }
        }

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
            if ($driver === 'mysql' && $dumpSuccess && File::exists($tempSqlFile)) {
                $zip->addFile($tempSqlFile, "database_dump_{$timestamp}.sql");
            } else {
                $sqlitePath = database_path('database.sqlite');
                if (File::exists($sqlitePath)) {
                    $zip->addFile($sqlitePath, 'database.sqlite');
                }
            }

            $manifestContent = json_encode([
                'created_at' => now()->toIso8601String(),
                'app_env' => config('app.env'),
                'db_driver' => $driver,
                'mysql_dump_status' => $driver === 'mysql' ? ($dumpSuccess ? 'success' : 'failed') : 'n/a',
                'mysql_error' => $dumpErrorMessage,
                'laravel_version' => app()->version(),
            ], JSON_PRETTY_PRINT);
            $zip->addFromString('backup_manifest.json', $manifestContent);

            $zip->close();
        }

        if ($tempSqlFile && File::exists($tempSqlFile)) {
            File::delete($tempSqlFile);
        }

        if ($driver === 'mysql' && ! $dumpSuccess && ! app()->environment('testing')) {
            ActivityLogService::log(
                'backup_error',
                'system',
                "MySQL backup completed with warnings/errors: {$dumpErrorMessage}"
            );
        }

        ActivityLogService::log(
            'create_backup',
            'system',
            "Created system backup archive {$filename}"
        );

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Backup created successfully.',
                'filename' => $filename,
            ]);
        }

        return redirect()->back()->with('success', "Backup archive {$filename} created successfully.");
    }

    public function download(Request $request, string $filename)
    {
        $this->checkSuperAdmin($request);

        // Sanitize filename to prevent directory traversal
        $safeFilename = basename($filename);
        $filePath = storage_path('app/backups/' . $safeFilename);

        if (! File::exists($filePath)) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Backup file not found.'], 404);
            }
            abort(404, 'Backup file not found.');
        }

        ActivityLogService::log(
            'download_backup',
            'system',
            "Downloaded system backup archive {$safeFilename}"
        );

        return response()->download($filePath, $safeFilename);
    }

    public function destroy(Request $request, string $filename)
    {
        $this->checkSuperAdmin($request);

        $safeFilename = basename($filename);
        $filePath = storage_path('app/backups/' . $safeFilename);

        if (File::exists($filePath)) {
            File::delete($filePath);
            ActivityLogService::log(
                'delete_backup',
                'system',
                "Deleted system backup archive {$safeFilename}"
            );
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "Backup archive {$safeFilename} deleted successfully.",
            ]);
        }

        return redirect()->back()->with('success', "Backup archive {$safeFilename} deleted successfully.");
    }
}
