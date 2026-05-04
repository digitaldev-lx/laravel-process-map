<?php

declare(strict_types=1);

namespace Tests\Fixtures\App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class LeadAssignedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @return list<string>
     */
    public function via(mixed $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(mixed $notifiable): mixed
    {
        return null;
    }

    public function toDatabase(mixed $notifiable): mixed
    {
        return null;
    }
}
