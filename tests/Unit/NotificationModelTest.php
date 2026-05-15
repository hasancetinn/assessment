<?php

namespace Tests\Unit;

use App\Models\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_mark_notification_as_sent(): void
    {
        $notification = Notification::create([
            'idempotency_key' => 'test-key',
            'recipient' => '+905551234567',
            'channel' => 'sms',
            'content' => 'Test',
            'status' => 'pending',
        ]);

        $notification->markAsSent('external-123');

        $this->assertEquals('sent', $notification->fresh()->status);
        $this->assertEquals('external-123', $notification->fresh()->external_message_id);
        $this->assertNotNull($notification->fresh()->sent_at);
    }

    public function test_can_mark_notification_as_failed(): void
    {
        $notification = Notification::create([
            'idempotency_key' => 'test-key',
            'recipient' => '+905551234567',
            'channel' => 'sms',
            'content' => 'Test',
            'status' => 'pending',
        ]);

        $notification->markAsFailed('Connection timeout');

        $this->assertEquals('failed', $notification->fresh()->status);
        $this->assertEquals('Connection timeout', $notification->fresh()->error_message);
    }

    public function test_can_cancel_pending_notification(): void
    {
        $notification = Notification::create([
            'idempotency_key' => 'test-key',
            'recipient' => '+905551234567',
            'channel' => 'sms',
            'content' => 'Test',
            'status' => 'pending',
        ]);

        $result = $notification->cancel();

        $this->assertTrue($result);
        $this->assertEquals('cancelled', $notification->fresh()->status);
    }

    public function test_cannot_cancel_sent_notification(): void
    {
        $notification = Notification::create([
            'idempotency_key' => 'test-key',
            'recipient' => '+905551234567',
            'channel' => 'sms',
            'content' => 'Test',
            'status' => 'sent',
        ]);

        $result = $notification->cancel();

        $this->assertFalse($result);
        $this->assertEquals('sent', $notification->fresh()->status);
    }

    public function test_can_increment_retry_count(): void
    {
        $notification = Notification::create([
            'idempotency_key' => 'test-key',
            'recipient' => '+905551234567',
            'channel' => 'sms',
            'content' => 'Test',
            'status' => 'pending',
        ]);

        $notification->incrementRetry();
        $notification->incrementRetry();

        $this->assertEquals(2, $notification->fresh()->retry_count);
    }
}
