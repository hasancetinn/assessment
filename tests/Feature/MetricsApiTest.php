<?php

namespace Tests\Feature;

use App\Models\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MetricsApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_get_metrics(): void
    {
        Notification::create([
            'idempotency_key' => 'key-1',
            'recipient' => '+905551234567',
            'channel' => 'sms',
            'content' => 'Test',
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        Notification::create([
            'idempotency_key' => 'key-2',
            'recipient' => '+905551234568',
            'channel' => 'email',
            'content' => 'Test',
            'status' => 'failed',
        ]);

        $response = $this->getJson('/api/v1/metrics');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'timestamp',
                'queue_depth',
                'success_failure_rates',
                'latency',
                'channels',
                'rate_limits',
            ]);
    }
}
