<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class FailedJobController extends Controller
{
    public function index(): JsonResponse
    {
        $failedJobs = DB::table('failed_jobs')
            ->orderBy('failed_at', 'desc')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $failedJobs->items(),
            'pagination' => [
                'total' => $failedJobs->total(),
                'per_page' => $failedJobs->perPage(),
                'current_page' => $failedJobs->currentPage(),
                'last_page' => $failedJobs->lastPage(),
            ],
        ]);
    }

    public function show(string $id): JsonResponse
    {
        $failedJob = DB::table('failed_jobs')->where('id', $id)->first();

        if (!$failedJob) {
            return response()->json([
                'success' => false,
                'message' => 'Failed job not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $failedJob,
        ]);
    }

    public function retry(string $id): JsonResponse
    {
        $failedJob = DB::table('failed_jobs')->where('id', $id)->first();

        if (!$failedJob) {
            return response()->json([
                'success' => false,
                'message' => 'Failed job not found',
            ], 404);
        }

        Artisan::call('queue:retry', ['id' => [$id]]);

        return response()->json([
            'success' => true,
            'message' => 'Job queued for retry',
        ]);
    }

    public function retryAll(): JsonResponse
    {
        $count = DB::table('failed_jobs')->count();

        if ($count === 0) {
            return response()->json([
                'success' => true,
                'message' => 'No failed jobs to retry',
                'count' => 0,
            ]);
        }

        Artisan::call('queue:retry', ['id' => ['all']]);

        return response()->json([
            'success' => true,
            'message' => "Queued {$count} jobs for retry",
            'count' => $count,
        ]);
    }

    public function delete(string $id): JsonResponse
    {
        $deleted = DB::table('failed_jobs')->where('id', $id)->delete();

        if (!$deleted) {
            return response()->json([
                'success' => false,
                'message' => 'Failed job not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Failed job deleted',
        ]);
    }

    public function flush(): JsonResponse
    {
        $count = DB::table('failed_jobs')->count();
        
        Artisan::call('queue:flush');

        return response()->json([
            'success' => true,
            'message' => "Deleted {$count} failed jobs",
            'count' => $count,
        ]);
    }
}
