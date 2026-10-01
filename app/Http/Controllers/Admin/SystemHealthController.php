<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WebhookLog;
use App\Models\WooCommerceSyncLog;
use App\Services\WooCommerce\WooCommerceClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class SystemHealthController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        if (! $user || ! $user->isSuperAdmin()) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Super Admin access required.'], 403);
            }
            abort(403, 'Super Admin access required.');
        }

        // 1. Database Check
        $dbStatus = 'Healthy';
        try {
            DB::connection()->getPdo();
        } catch (\Throwable $e) {
            $dbStatus = 'Failed';
        }

        // 2. Storage Writable Check
        $storageWritable = is_writable(storage_path()) ? 'Healthy' : 'Failed';

        // 3. Failed Jobs Count
        $failedJobsCount = 0;
        try {
            $failedJobsCount = DB::table('failed_jobs')->count();
        } catch (\Throwable $e) {
            // table might not exist in un-migrated test environment
        }

        // 4. WooCommerce Sync Metrics
        $lastSyncTime = null;
        $failedSyncCount = 0;
        try {
            $lastSyncTime = WooCommerceSyncLog::where('status', 'success')->latest()->value('created_at');
            $failedSyncCount = WooCommerceSyncLog::where('status', 'failed')->count();
        } catch (\Throwable $e) {
        }

        // 5. Webhook Failures
        $webhookFailures = 0;
        try {
            $webhookFailures = WebhookLog::where('status', 'failed')->count();
        } catch (\Throwable $e) {
        }

        // 6. WooCommerce Connection Test
        $wcStatus = 'Unknown';
        try {
            $client = new WooCommerceClient();
            $testRes = $client->testConnection();
            $wcStatus = (! empty($testRes['success'])) ? 'Healthy' : 'Warning';
        } catch (\Throwable $e) {
            $wcStatus = 'Failed';
        }

        $healthMetrics = [
            'laravel_version' => app()->version(),
            'php_version' => PHP_VERSION,
            'environment' => config('app.env'),
            'database_status' => $dbStatus,
            'queue_driver' => config('queue.default', 'sync'),
            'failed_jobs_count' => $failedJobsCount,
            'storage_writable' => $storageWritable,
            'woocommerce_status' => $wcStatus,
            'last_successful_sync' => $lastSyncTime ? $lastSyncTime->toDateTimeString() : 'None',
            'failed_sync_count' => $failedSyncCount,
            'webhook_failures' => $webhookFailures,
            'mail_driver' => config('mail.default', 'smtp'),
            'cache_driver' => config('cache.default', 'file'),
        ];

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'health' => $healthMetrics,
            ]);
        }

        return view('admin.system.health', [
            'health' => $healthMetrics,
        ]);
    }
}
