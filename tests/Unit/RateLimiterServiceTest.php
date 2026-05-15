<?php

namespace Tests\Unit;

use App\Services\RateLimiterService;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

class RateLimiterServiceTest extends TestCase
{
    protected RateLimiterService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new RateLimiterService;
        Redis::flushdb();
    }

    public function test_can_get_current_count(): void
    {
        $count = $this->service->getCurrentCount('sms');
        $this->assertEquals(0, $count);
    }

    public function test_wait_for_slot_increments_counter(): void
    {
        $this->service->waitForSlot('sms');
        $count = $this->service->getCurrentCount('sms');
        $this->assertEquals(1, $count);
    }
}
