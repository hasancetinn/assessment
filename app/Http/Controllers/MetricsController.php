<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Services\RateLimiterService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class MetricsController extends Controller
{
    public function __invoke(RateLimiterService $rateLimiter): JsonResponse
    {
        $queueDepth = $this->getQueueDepth();
        $successFailureRates = $this->getSuccessFailureRates();
        $latencyMetrics = $this->getLatencyMetrics();
        $channelMetrics = $this->getChannelMetrics();
        $rateLimitMetrics = $this->getRateLimitMetrics($rateLimiter);

        return response()->json([
            'timestamp' => now()->toIso8601String(),
            'queue_depth' => $queueDepth,
            'success_failure_rates' => $successFailureRates,
            'latency' => $latencyMetrics,
            'channels' => $channelMetrics,
            'rate_limits' => $rateLimitMetrics,
        ]);
    }

    protected function getQueueDepth(): array
    {
        return [
            'high' => DB::table('jobs')->where('queue', 'high')->count(),
            'default' => DB::table('jobs')->where('queue', 'default')->count(),
            'low' => DB::table('jobs')->where('queue', 'low')->count(),
            'total' => DB::table('jobs')->count(),
        ];
    }

    protected function getSuccessFailureRates(): array
    {
        $last24Hours = now()->subDay();

        $total = Notification::where('created_at', '>=', $last24Hours)->count();
        $sent = Notification::where('created_at', '>=', $last24Hours)
            ->where('status', 'sent')->count();
        $failed = Notification::where('created_at', '>=', $last24Hours)
            ->where('status', 'failed')->count();

        return [
            'total' => $total,
            'sent' => $sent,
            'failed' => $failed,
            'success_rate' => $total > 0 ? round(($sent / $total) * 100, 2) : 0,
            'failure_rate' => $total > 0 ? round(($failed / $total) * 100, 2) : 0,
        ];
    }

    protected function getLatencyMetrics(): array
    {
        $avgLatency = Notification::whereNotNull('sent_at')
            ->where('created_at', '>=', now()->subDay())
            ->selectRaw('AVG(TIMESTAMPDIFF(SECOND, created_at, sent_at)) as avg_seconds')
            ->first();

        return [
            'average_seconds' => round($avgLatency->avg_seconds ?? 0, 2),
        ];
    }

    protected function getChannelMetrics(): array
    {
        $channels = ['sms', 'email', 'push'];
        $metrics = [];

        foreach ($channels as $channel) {
            $total = Notification::where('channel', $channel)
                ->where('created_at', '>=', now()->subDay())
                ->count();

            $sent = Notification::where('channel', $channel)
                ->where('status', 'sent')
                ->where('created_at', '>=', now()->subDay())
                ->count();

            $metrics[$channel] = [
                'total' => $total,
                'sent' => $sent,
                'success_rate' => $total > 0 ? round(($sent / $total) * 100, 2) : 0,
            ];
        }

        return $metrics;
    }

    protected function getRateLimitMetrics(RateLimiterService $rateLimiter): array
    {
        return [
            'sms' => [
                'current' => $rateLimiter->getCurrentCount('sms'),
                'limit' => config('services.notification.rate_limit_per_channel', 100),
            ],
            'email' => [
                'current' => $rateLimiter->getCurrentCount('email'),
                'limit' => config('services.notification.rate_limit_per_channel', 100),
            ],
            'push' => [
                'current' => $rateLimiter->getCurrentCount('push'),
                'limit' => config('services.notification.rate_limit_per_channel', 100),
            ],
        ];
    }
}
