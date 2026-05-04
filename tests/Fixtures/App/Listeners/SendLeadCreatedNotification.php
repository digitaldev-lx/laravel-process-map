<?php

declare(strict_types=1);

namespace Tests\Fixtures\App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Tests\Fixtures\App\Events\LeadCreated;
use Tests\Fixtures\App\Notifications\LeadAssignedNotification;

class SendLeadCreatedNotification implements ShouldQueue
{
    public function handle(LeadCreated $event): void
    {
        $event->lead->notify(new LeadAssignedNotification);
    }
}
