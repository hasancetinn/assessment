<?php

namespace Tests\Feature;

use App\Models\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_notification(): void
    {
        $response = $this->postJson('/api/v1/notifications', [
            'recipient' => '+905551234567',
            'channel' => 'sms',
            'content' => 'Test message',
            'priority' => 'high',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'data' => ['id', 'recipient', 'channel', 'content', 'status'],
            ]);

        $this->assertDatabaseHas('notifications', [
            'recipient' => '+905551234567',
            'channel' => 'sms',
        ]);

        $this->assertEquals(1, Notification::count());
    }

    public function test_can_create_batch_notifications(): void
    {
        $response = $this->postJson('/api/v1/notifications/batch', [
            'notifications' => [
                [
                    'recipient' => '+905551234567',
                    'channel' => 'sms',
                    'content' => 'Message 1',
                ],
                [
                    'recipient' => 'test@example.com',
                    'channel' => 'email',
                    'content' => 'Message 2',
                ],
            ],
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'data' => ['batch_id', 'notifications', 'total'],
            ]);

        $this->assertEquals(2, Notification::count());
    }

    public function test_batch_size_limit_is_enforced(): void
    {
        $notifications = array_fill(0, 1001, [
            'recipient' => '+905551234567',
            'channel' => 'sms',
            'content' => 'Test',
        ]);

        $response = $this->postJson('/api/v1/notifications/batch', [
            'notifications' => $notifications,
        ]);

        $response->assertStatus(422);
    }

    public function test_can_get_notification_by_id(): void
    {
        $notification = Notification::create([
            'idempotency_key' => 'test-key-1',
            'recipient' => '+905551234567',
            'channel' => 'sms',
            'content' => 'Test message',
            'priority' => 'normal',
            'status' => 'pending',
        ]);

        $response = $this->getJson("/api/v1/notifications/{$notification->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $notification->id,
                    'recipient' => '+905551234567',
                ],
            ]);
    }

    public function test_can_get_notifications_by_batch_id(): void
    {
        $batchId = 'batch-123';

        Notification::create([
            'batch_id' => $batchId,
            'idempotency_key' => 'test-key-1',
            'recipient' => '+905551234567',
            'channel' => 'sms',
            'content' => 'Test 1',
            'status' => 'pending',
        ]);

        Notification::create([
            'batch_id' => $batchId,
            'idempotency_key' => 'test-key-2',
            'recipient' => '+905551234568',
            'channel' => 'sms',
            'content' => 'Test 2',
            'status' => 'pending',
        ]);

        $response = $this->getJson("/api/v1/notifications/batch/{$batchId}");

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data')
            ->assertJson(['total' => 2]);
    }

    public function test_can_cancel_pending_notification(): void
    {
        $notification = Notification::create([
            'idempotency_key' => 'test-key-1',
            'recipient' => '+905551234567',
            'channel' => 'sms',
            'content' => 'Test message',
            'status' => 'pending',
        ]);

        $response = $this->postJson("/api/v1/notifications/{$notification->id}/cancel");

        $response->assertStatus(200);

        $this->assertDatabaseHas('notifications', [
            'id' => $notification->id,
            'status' => 'cancelled',
        ]);
    }

    public function test_cannot_cancel_sent_notification(): void
    {
        $notification = Notification::create([
            'idempotency_key' => 'test-key-1',
            'recipient' => '+905551234567',
            'channel' => 'sms',
            'content' => 'Test message',
            'status' => 'sent',
        ]);

        $response = $this->postJson("/api/v1/notifications/{$notification->id}/cancel");

        $response->assertStatus(400);
    }

    public function test_can_list_notifications_with_filters(): void
    {
        Notification::create([
            'idempotency_key' => 'key-1',
            'recipient' => '+905551234567',
            'channel' => 'sms',
            'content' => 'Test',
            'status' => 'sent',
        ]);

        Notification::create([
            'idempotency_key' => 'key-2',
            'recipient' => 'test@example.com',
            'channel' => 'email',
            'content' => 'Test',
            'status' => 'pending',
        ]);

        $response = $this->getJson('/api/v1/notifications?channel=sms&status=sent');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    public function test_idempotency_prevents_duplicate_notifications(): void
    {
        $payload = [
            'recipient' => '+905551234567',
            'channel' => 'sms',
            'content' => 'Test message',
            'idempotency_key' => 'unique-key-123',
        ];

        $this->postJson('/api/v1/notifications', $payload)->assertStatus(201);
        $this->postJson('/api/v1/notifications', $payload)->assertStatus(201);

        $this->assertEquals(1, Notification::count());
    }

    public function test_content_validation_by_channel(): void
    {
        $response = $this->postJson('/api/v1/notifications', [
            'recipient' => '+905551234567',
            'channel' => 'sms',
            'content' => str_repeat('a', 161),
        ]);

        $response->assertStatus(422);
    }

    public function test_pagination_works(): void
    {
        for ($i = 0; $i < 20; $i++) {
            Notification::create([
                'idempotency_key' => "key-{$i}",
                'recipient' => '+905551234567',
                'channel' => 'sms',
                'content' => "Test {$i}",
                'status' => 'pending',
            ]);
        }

        $response = $this->getJson('/api/v1/notifications?per_page=10');

        $response->assertStatus(200)
            ->assertJsonCount(10, 'data')
            ->assertJsonPath('pagination.total', 20)
            ->assertJsonPath('pagination.per_page', 10);
    }
}
