<?php

namespace App\Http\Requests\Jobs;

use App\Models\Notification;
use App\Services\RateLimiterService;
use App\Services\WebhookService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ProcessNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;

    public $backoff = 60;

    public $timeout = 30;

    protected Notification $notification;

    public function __construct(Notification $notification)
    {
        $this->notification = $notification;
    }

    public function handle(RateLimiterService $rateLimiter, WebhookService $webhook): void
    {
        if ($this->notification->status !== 'pending') {
            return;
        }

        $correlationId = Str::uuid()->toString();

        Log::info('Processing notification', [
            'correlation_id' => $correlationId,
            'notification_id' => $this->notification->id,
            'channel' => $this->notification->channel,
            'priority' => $this->notification->priority,
            'attempt' => $this->attempts(),
        ]);

        $this->notification->markAsProcessing();

        $rateLimiter->waitForSlot($this->notification->channel);

        try {
            $response = $webhook->send(
                $this->notification->recipient,
                $this->notification->channel,
                $this->notification->content
            );

            $this->notification->markAsSent($response['messageId']);

            Log::info('Notification sent successfully', [
                'correlation_id' => $correlationId,
                'notification_id' => $this->notification->id,
                'external_message_id' => $response['messageId'],
            ]);

        } catch (\Exception $e) {
            $this->notification->incrementRetry();

            Log::error('Notification delivery failed', [
                'correlation_id' => $correlationId,
                'notification_id' => $this->notification->id,
                'attempt' => $this->attempts(),
                'error' => $e->getMessage(),
            ]);

            if ($this->attempts() >= $this->tries) {
                $this->notification->markAsFailed($e->getMessage());

                Log::error('Notification permanently failed', [
                    'correlation_id' => $correlationId,
                    'notification_id' => $this->notification->id,
                    'total_attempts' => $this->attempts(),
                ]);
            } else {
                $this->notification->update(['status' => 'pending']);
                throw $e;
            }
        }
    }

    public function backoff(): array
    {
        return [60, 180, 300];
    }
}
