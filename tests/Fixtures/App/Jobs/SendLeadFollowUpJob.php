<?php

declare(strict_types=1);

namespace Tests\Fixtures\App\Jobs;

use Tests\Fixtures\App\Models\Lead;
use Tests\Fixtures\App\Notifications\LeadAssignedNotification;

class SendLeadFollowUpJob
{
    public function __construct(public readonly Lead $lead) {}

    public function handle(): void
    {
        $this->lead->notify(new LeadAssignedNotification);
    }

    public static function dispatch(Lead $lead): self
    {
        return new self($lead);
    }
}
