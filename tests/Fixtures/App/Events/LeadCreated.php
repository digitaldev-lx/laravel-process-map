<?php

declare(strict_types=1);

namespace Tests\Fixtures\App\Events;

use Tests\Fixtures\App\Models\Lead;

class LeadCreated
{
    public function __construct(public readonly Lead $lead) {}
}
