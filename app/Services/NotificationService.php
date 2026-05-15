<?php

namespace App\Services;

use App\Http\Requests\Jobs\ProcessNotificationJob;
use App\Models\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class NotificationService
{
    public function createNotification(array $data): Notification
    {
        $this->validateContent($data['channel'], $data['content']);
        
        $idempotencyKey = $data['idempotency_key'] ?? Str::uuid()->toString();
        
        $notification = Notification::firstOrCreate(
            ['idempotency_key' => $idempotencyKey],
            [
                'recipient' => $data['recipient'],
                'channel' => $data['channel'],
                'content' => $data['content'],
                'priority' => $data['priority'] ?? 'normal',
                'status' => 'pending',
                'scheduled_at' => $data['scheduled_at'] ?? null,
            ]
        );

        if ($notification->wasRecentlyCreated && !$notification->scheduled_at) {
            $this->dispatchNotification($notification);
        }

        return $notification;
    }

    public function createBatch(array $notifications): array
    {
        if (count($notifications) > config('services.notification.batch_size_limit', 1000)) {
            throw ValidationException::withMessages([
                'notifications' => ['Batch size exceeds maximum limit of 1000']
            ]);
        }

        $batchId = Str::uuid()->toString();
        $created = [];

        foreach ($notifications as $data) {
            $data['batch_id'] = $batchId;
            $created[] = $this->createNotification($data);
        }

        return [
            'batch_id' => $batchId,
            'notifications' => $created,
            'total' => count($created),
        ];
    }

    public function cancelNotification(string $id): bool
    {
        $notification = Notification::findOrFail($id);
        return $notification->cancel();
    }

    public function getNotification(string $id): Notification
    {
        return Notification::findOrFail($id);
    }

    public function getByBatchId(string $batchId)
    {
        return Notification::byBatch($batchId)->get();
    }

    protected function dispatchNotification(Notification $notification): void
    {
        $queue = match($notification->priority) {
            'high' => 'high',
            'low' => 'low',
            default => 'default',
        };

        ProcessNotificationJob::dispatch($notification)->onQueue($queue);
    }

    protected function validateContent(string $channel, string $content): void
    {
        $limits = [
            'sms' => 160,
            'email' => 10000,
            'push' => 256,
        ];

        $limit = $limits[$channel] ?? 1000;

        if (strlen($content) > $limit) {
            throw ValidationException::withMessages([
                'content' => ["Content exceeds maximum length of {$limit} characters for {$channel}"]
            ]);
        }

        if (empty(trim($content))) {
            throw ValidationException::withMessages([
                'content' => ['Content is required']
            ]);
        }
    }
}
