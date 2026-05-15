<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class HealthCheckController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'redis' => $this->checkRedis(),
            'queue' => $this->checkQueue(),
        ];

        $healthy = ! in_array(false, array_column($checks, 'healthy'));

        return response()->json([
            'status' => $healthy ? 'healthy' : 'unhealthy',
            'timestamp' => now()->toIso8601String(),
            'checks' => $checks,
        ], $healthy ? 200 : 503);
    }

    protected function checkDatabase(): array
    {
        try {
            DB::connection()->getPdo();

            return [
                'healthy' => true,
                'message' => 'Database connection successful',
            ];
        } catch (\Exception $e) {
            return [
                'healthy' => false,
                'message' => 'Database connection failed: '.$e->getMessage(),
            ];
        }
    }

    protected function checkRedis(): array
    {
        try {
            Redis::ping();

            return [
                'healthy' => true,
                'message' => 'Redis connection successful',
            ];
        } catch (\Exception $e) {
            return [
                'healthy' => false,
                'message' => 'Redis connection failed: '.$e->getMessage(),
            ];
        }
    }

    protected function checkQueue(): array
    {
        try {
            $jobCount = DB::table('jobs')->count();

            return [
                'healthy' => true,
                'message' => 'Queue system operational',
                'pending_jobs' => $jobCount,
            ];
        } catch (\Exception $e) {
            return [
                'healthy' => false,
                'message' => 'Queue check failed: '.$e->getMessage(),
            ];
        }
    }
}
