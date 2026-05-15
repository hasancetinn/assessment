<?php

namespace App\Console\Commands;

use App\Jobs\ProcessNotificationJob;
use App\Models\Notification;
use Illuminate\Console\Command;

class ProcessScheduledNotifications extends Command
{
    protected $signature = 'notifications:process-scheduled';

    protected $description = 'Process scheduled notifications that are due for delivery';

    public function handle(): int
    {
        $notifications = Notification::where('status', 'pending')
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<=', now())
            ->get();

        if ($notifications->isEmpty()) {
            $this->info('No scheduled notifications to process');

            return self::SUCCESS;
        }

        $count = 0;
        foreach ($notifications as $notification) {
            $queue = match ($notification->priority) {
                'high' => 'high',
                'low' => 'low',
                default => 'default',
            };

            ProcessNotificationJob::dispatch($notification)->onQueue($queue);
            $count++;
        }

        $this->info("Dispatched {$count} scheduled notifications");

        return self::SUCCESS;
    }
}
