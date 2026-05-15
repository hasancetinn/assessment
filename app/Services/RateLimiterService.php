<?php

namespace App\Services;

use Illuminate\Support\Facades\Redis;

class RateLimiterService
{
    protected int $maxPerSecond;

    protected int $window;

    public function __construct()
    {
        $this->maxPerSecond = config('services.notification.rate_limit_per_channel', 100);
        $this->window = config('services.notification.rate_limit_window', 1);
    }

    public function waitForSlot(string $channel): void
    {
        $key = "rate_limit:{$channel}";
        $currentSecond = now()->timestamp;

        while (true) {
            $count = Redis::get("{$key}:{$currentSecond}") ?? 0;

            if ($count < $this->maxPerSecond) {
                Redis::incr("{$key}:{$currentSecond}");
                Redis::expire("{$key}:{$currentSecond}", $this->window + 1);
                break;
            }

            usleep(100000);
            $currentSecond = now()->timestamp;
        }
    }

    public function getCurrentCount(string $channel): int
    {
        $key = "rate_limit:{$channel}";
        $currentSecond = now()->timestamp;

        return (int) (Redis::get("{$key}:{$currentSecond}") ?? 0);
    }
}
