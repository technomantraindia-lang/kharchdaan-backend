<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class FailedJobController extends Controller
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

        $failedJobs = DB::table('failed_jobs')
            ->orderBy('failed_at', 'desc')
            ->paginate(20);

        // Transform payload to mask sensitive secrets (passwords, tokens, api keys)
        $failedJobs->getCollection()->transform(function ($job) {
            $payload = json_decode($job->payload, true);
            if (is_array($payload)) {
                $job->job_name = $payload['displayName'] ?? $job->queue;
            } else {
                $job->job_name = $job->queue;
            }

            // Mask exception secrets
            $exception = $job->exception;
            $maskedException = preg_replace('/(consumer_secret|secret|password|bearer\s+[A-Za-z0-9\-\._~\+\/]+=*)=[^&\s\r\n]+/i', '$1=***MASKED***', $exception);
            $job->exception_summary = substr($maskedException, 0, 300) . '...';

            return $job;
        });

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'data' => $failedJobs,
            ]);
        }

        return view('admin.system.failed_jobs', [
            'failedJobs' => $failedJobs,
        ]);
    }

    public function retry(Request $request, $id)
    {
        $user = auth()->user();
        if (! $user || ! $user->isSuperAdmin()) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Super Admin access required.'], 403);
            }
            abort(403, 'Super Admin access required.');
        }

        Artisan::call('queue:retry', ['id' => [$id]]);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "Failed job #{$id} queued for retry.",
            ]);
        }

        return redirect()->back()->with('success', "Failed job #{$id} queued for retry.");
    }

    public function retryAll(Request $request)
    {
        $user = auth()->user();
        if (! $user || ! $user->isSuperAdmin()) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Super Admin access required.'], 403);
            }
            abort(403, 'Super Admin access required.');
        }

        Artisan::call('queue:retry', ['id' => ['all']]);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'All failed jobs queued for retry.',
            ]);
        }

        return redirect()->back()->with('success', 'All failed jobs queued for retry.');
    }

    public function destroy(Request $request, $id)
    {
        $user = auth()->user();
        if (! $user || ! $user->isSuperAdmin()) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Super Admin access required.'], 403);
            }
            abort(403, 'Super Admin access required.');
        }

        DB::table('failed_jobs')->where('id', $id)->delete();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "Failed job #{$id} deleted.",
            ]);
        }

        return redirect()->back()->with('success', "Failed job #{$id} deleted.");
    }
}
