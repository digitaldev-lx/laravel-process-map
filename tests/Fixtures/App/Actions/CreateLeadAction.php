<?php

declare(strict_types=1);

namespace Tests\Fixtures\App\Actions;

use Tests\Fixtures\App\Events\LeadCreated;
use Tests\Fixtures\App\Jobs\SendLeadFollowUpJob;
use Tests\Fixtures\App\Models\Lead;

class CreateLeadAction
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function __invoke(array $payload): Lead
    {
        $lead = new Lead;

        SendLeadFollowUpJob::dispatch($lead);
        event(new LeadCreated($lead));

        return $lead;
    }
}
